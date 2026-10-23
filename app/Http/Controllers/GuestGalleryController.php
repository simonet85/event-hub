<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Galerie vue par un invité (identifié par son jeton) : affichage, téléchargement d'une photo ou d'un lot (.zip).
 * Les photos ne sont jamais servies par une URL publique : seul le lien d'invitation y donne accès.
 */
class GuestGalleryController extends Controller
{
    /** Photos par archive : au-delà, une sélection est tronquée (un téléchargement « tout » n'est pas limité). */
    public const MAX_SELECTION = 100;

    public function show(Request $request, Guest $guest, Photo $photo): StreamedResponse
    {
        $this->authorizePhoto($guest, $photo);

        $path = $request->query('taille') === 'mini' && $photo->thumb_path ? $photo->thumb_path : $photo->path;

        return Storage::disk('local')->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function download(Guest $guest, Photo $photo): StreamedResponse
    {
        $this->authorizePhoto($guest, $photo);

        return Storage::disk('local')->download($photo->path, "{$guest->event->slug}-{$photo->id}.jpg", [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /**
     * Archive .zip de toute la galerie, ou des photos demandées (`ids[]`). Les JPEG sont déjà compressés : on les range sans recompression.
     */
    public function archive(Request $request, Guest $guest): BinaryFileResponse
    {
        $ids = collect($request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer'],
        ])['ids'] ?? [])->unique()->take(self::MAX_SELECTION)->values();

        $photos = $guest->event->galleryPhotos()->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids))->get();
        abort_if($photos->isEmpty(), 404);

        $disk = Storage::disk('local');
        $slug = $guest->event->slug;
        $tmp = tempnam(sys_get_temp_dir(), 'galerie');

        $zip = new ZipArchive;
        abort_unless($zip->open($tmp, ZipArchive::OVERWRITE) === true, 500);

        foreach ($photos->values() as $i => $photo) {
            if (! $disk->exists($photo->path)) {
                continue;
            }

            $name = sprintf('%s-%03d.jpg', $slug, $i + 1);
            $zip->addFile($disk->path($photo->path), $name);
            $zip->setCompressionName($name, ZipArchive::CM_STORE);
        }

        $zip->close();

        $response = response()->download($tmp, "{$slug}-photos.zip", [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);

        // Un téléchargement de fichier est « public » par défaut : l'archive ne doit être gardée par aucun cache partagé.
        $response->setPrivate();

        return $response;
    }

    private function authorizePhoto(Guest $guest, Photo $photo): void
    {
        abort_unless($photo->event_id === $guest->event_id && $photo->guest_id === null, 404);
    }
}
