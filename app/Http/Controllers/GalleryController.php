<?php

namespace App\Http\Controllers;

use App\Http\Requests\GalleryUploadRequest;
use App\Models\Event;
use App\Models\Photo;
use App\Services\GalleryImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

/**
 * Galerie de l'événement, côté hôte : l'hôte (ou son photographe) envoie les photos que les invités verront et téléchargeront.
 */
class GalleryController extends Controller
{
    public const MAX_PHOTOS_PER_EVENT = 500;

    public function index(Event $event): Response
    {
        Gate::authorize('view', $event);

        return Inertia::render('Events/Gallery', [
            'event' => ['title' => $event->title, 'slug' => $event->slug],
            'photos' => $event->galleryPhotos()->get()->map(fn (Photo $p) => [
                'id' => $p->id,
                'thumb' => route('photos.show', [$p, 'taille' => 'mini']),
                'url' => route('photos.show', $p),
            ]),
            'limits' => [
                'per_event' => self::MAX_PHOTOS_PER_EVENT,
                'per_upload' => GalleryUploadRequest::MAX_FILES,
                'size_mb' => GalleryUploadRequest::MAX_SIZE_KB / 1024,
            ],
        ]);
    }

    public function store(GalleryUploadRequest $request, Event $event, GalleryImages $images): RedirectResponse
    {
        Gate::authorize('update', $event);

        $files = $request->file('photos');

        if ($event->galleryPhotos()->count() + count($files) > self::MAX_PHOTOS_PER_EVENT) {
            return back()->withErrors(['photos' => __('La galerie est limitée à :max photos.', ['max' => self::MAX_PHOTOS_PER_EVENT])]);
        }

        $added = 0;
        foreach ($files as $file) {
            try {
                $images->store($event, $file);
                $added++;
            } catch (RuntimeException) {
                // Une image illisible n'empêche pas les autres : elle est signalée à la fin.
            }
        }

        if ($added < count($files)) {
            return back()->withErrors(['photos' => __(':count photo(s) n\'ont pas pu être lues et ont été ignorées.', ['count' => count($files) - $added])]);
        }

        return back()->with('gallery_added', $added);
    }

    public function destroy(Event $event, Photo $photo): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($photo->event_id === $event->id && $photo->guest_id === null, 404);

        $photo->delete();

        return back();
    }
}
