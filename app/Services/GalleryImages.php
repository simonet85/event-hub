<?php

namespace App\Services;

use App\Enums\ModerationStatus;
use App\Models\Event;
use App\Models\Photo;
use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Prépare les photos de la galerie : orientation corrigée, réduction, miniature, et ré-encodage en JPEG, ce qui
 * retire toutes les métadonnées (position GPS comprise). Seules ces versions sont gardées, jamais l'original.
 */
class GalleryImages
{
    /** Plus grand côté de la photo servie aux invités. */
    public const MAX_SIDE = 2400;

    /** Plus grand côté de la miniature de la grille. */
    public const THUMB_SIDE = 480;

    /**
     * @throws RuntimeException si le fichier n'est pas une image lisible
     */
    public function store(Event $event, UploadedFile $file): Photo
    {
        $this->allowMemory();

        // Une transparence (PNG, WebP) est aplatie sur fond blanc ; un JPEG n'en a pas.
        $flatten = $file->getMimeType() !== 'image/jpeg';
        $directory = "photos/{$event->id}";
        $name = (string) Str::uuid();
        $disk = Storage::disk('local');

        // La miniature vient de la version réduite, plus légère à traiter que l'original : à 24 Mpx, l'original décodé pèse près de 100 Mo.
        $large = $this->fit($this->decode($file), self::MAX_SIDE);
        $disk->put("{$directory}/{$name}.jpg", $this->jpeg($large, 85, $flatten));
        $disk->put("{$directory}/{$name}-mini.jpg", $this->jpeg($this->fit($large, self::THUMB_SIDE), 78, $flatten));

        return $event->photos()->create([
            'path' => "{$directory}/{$name}.jpg",
            'thumb_path' => "{$directory}/{$name}-mini.jpg",
            'status' => ModerationStatus::Approved,
        ]);
    }

    private function decode(UploadedFile $file): GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if (! $image) {
            throw new RuntimeException('Image illisible.');
        }

        // L'orientation est lue dans les métadonnées avant qu'elles ne disparaissent au ré-encodage.
        $orientation = 1;
        if ($file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')) {
            $orientation = (int) (@exif_read_data($file->getRealPath())['Orientation'] ?? 1);
        }

        $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;

        return $angle ? (imagerotate($image, $angle, 0) ?: $image) : $image;
    }

    /** Réduit l'image pour que son plus grand côté ne dépasse pas `$max` (jamais d'agrandissement). */
    private function fit(GdImage $image, int $max): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if (max($width, $height) <= $max) {
            return $image;
        }

        $scaled = $width >= $height
            ? imagescale($image, $max, -1, IMG_BICUBIC)
            : imagescale($image, (int) round($width * $max / $height), $max, IMG_BICUBIC);

        return $scaled ?: $image;
    }

    /** JPEG ; avec `$flatten`, sur fond blanc pour qu'une transparence ne devienne pas noire. */
    private function jpeg(GdImage $image, int $quality, bool $flatten): string
    {
        if ($flatten) {
            $flat = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($flat, 0, 0, (int) imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $image = $flat;
        }

        ob_start();
        imagejpeg($image, null, $quality);

        return (string) ob_get_clean();
    }

    /** Le décodage d'une grande photo dépasse les 128 Mo habituels : la limite est relevée le temps du traitement. */
    private function allowMemory(): void
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));

        if ($limit !== 0 && $limit < 512 * 1024 * 1024 && $limit > 0) {
            @ini_set('memory_limit', '512M');
        }
    }
}
