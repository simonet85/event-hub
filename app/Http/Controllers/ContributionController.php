<?php

namespace App\Http\Controllers;

use App\Enums\ModerationStatus;
use App\Http\Requests\GuestbookEntryRequest;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;

class ContributionController extends Controller
{
    public const MAX_ENTRIES_PER_GUEST = 5;

    /** Messages en attente de modération par événement : au-delà, l'hôte est protégé d'un déluge. */
    public const MAX_PENDING_ENTRIES_PER_EVENT = 200;

    /**
     * Un invité (identifié par son jeton) signe le livre d'or ; l'hôte modère avant publication.
     */
    public function storeEntry(GuestbookEntryRequest $request, Guest $guest): RedirectResponse
    {
        if ($guest->guestbookEntries()->count() >= self::MAX_ENTRIES_PER_GUEST) {
            return back()->withErrors(['message' => __('Vous avez atteint le nombre maximum de messages.')]);
        }

        $message = trim($request->validated('message'));

        // Un même message envoyé deux fois par le même invité est ignoré (double clic, copier-coller répété).
        if ($guest->guestbookEntries()->whereRaw('lower(message) = ?', [mb_strtolower($message)])->exists()) {
            return back()->withErrors(['message' => __('Vous avez déjà envoyé ce message.')]);
        }

        if ($guest->event->guestbookEntries()->where('status', ModerationStatus::Pending)->count() >= self::MAX_PENDING_ENTRIES_PER_EVENT) {
            return back()->withErrors(['message' => __('Les messages de cet événement sont en attente de validation par les hôtes. Réessayez plus tard.')]);
        }

        $guest->guestbookEntries()->create([
            'event_id' => $guest->event_id,
            'message' => $message,
        ]);

        return back()->with('contribution_saved', 'entry');
    }
}
