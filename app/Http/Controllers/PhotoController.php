<?php

namespace App\Http\Controllers;

use App\Enums\ModerationStatus;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PhotoController extends Controller
{
    /**
     * Sert une photo depuis le disque privé : publique si approuvée,
     * sinon réservée à l'hôte de l'événement.
     */
    public function show(Request $request, Photo $photo): StreamedResponse
    {
        $isPublic = $photo->status === ModerationStatus::Approved && ! $photo->event->isArchived();
        $isHost = $request->user()?->can('view', $photo->event) ?? false;

        abort_unless($isPublic || $isHost, 404);

        return Storage::disk('local')->response($photo->path, null, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $isPublic ? 'public, max-age=86400' : 'private, no-store',
        ]);
    }
}
