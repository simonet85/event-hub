<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\ShareImage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShareImageController extends Controller
{
    /**
     * Image Open Graph de l'événement (1200 × 630). Contenu au niveau de l'événement uniquement :
     * c'est la même information que la page publique, sans prénom d'invité ni donnée personnelle.
     */
    public function show(Event $event, ShareImage $images): BinaryFileResponse
    {
        abort_unless($images->available(), 404);

        $path = $images->path($event);

        return response()->file($path, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
            'ETag' => '"'.$images->fingerprint($event).'"',
        ]);
    }
}
