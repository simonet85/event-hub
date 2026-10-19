<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Http\Requests\RsvpRequest;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;

class RsvpController extends Controller
{
    /**
     * Enregistre (ou met à jour) la réponse de l'invité identifié par son jeton.
     */
    public function store(RsvpRequest $request, Guest $guest): RedirectResponse
    {
        $data = $request->validated();
        $accepted = $data['rsvp_status'] === RsvpStatus::Accepted->value;

        $guest->forceFill([
            'rsvp_status' => $data['rsvp_status'],
            'companions_count' => $accepted ? ($data['companions_count'] ?? 0) : 0,
            'dietary_restrictions' => $accepted ? ($data['dietary_restrictions'] ?? null) : null,
            'favorite_song' => $accepted ? ($data['favorite_song'] ?? null) : null,
            'rsvp_message' => $data['rsvp_message'] ?? null,
            'consent_at' => $accepted && filled($data['dietary_restrictions'] ?? null) ? now() : null,
            'responded_at' => now(),
        ])->save();

        return back()->with('rsvp_saved', true);
    }
}
