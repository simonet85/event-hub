<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    /**
     * Sert une photo depuis le disque privé, à l'hôte de l'événement seulement.
     */
    public function show(Request $request, Photo $photo): StreamedResponse
    {
        abort_unless($request->user()?->can('view', $photo->event), 404);

        $path = $request->query('taille') === 'mini' && $photo->thumb_path ? $photo->thumb_path : $photo->path;

        return Storage::disk('local')->response($path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
