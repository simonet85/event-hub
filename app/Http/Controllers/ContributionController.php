<?php

namespace App\Http\Controllers;

use App\Http\Requests\GuestbookEntryRequest;
use App\Http\Requests\PhotoUploadRequest;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;

class ContributionController extends Controller
{
    public const MAX_ENTRIES_PER_GUEST = 5;

    public const MAX_PHOTOS_PER_GUEST = 10;

    /**
     * Un invité (identifié par son jeton) signe le livre d'or ; l'hôte modère avant publication.
     */
    public function storeEntry(GuestbookEntryRequest $request, Guest $guest): RedirectResponse
    {
        if ($guest->guestbookEntries()->count() >= self::MAX_ENTRIES_PER_GUEST) {
            return back()->withErrors(['message' => 'Vous avez atteint le nombre maximum de messages.']);
        }

        $guest->guestbookEntries()->create([
            'event_id' => $guest->event_id,
            'message' => $request->validated('message'),
        ]);

        return back()->with('contribution_saved', 'entry');
    }

    public function storePhoto(PhotoUploadRequest $request, Guest $guest): RedirectResponse
    {
        if ($guest->photos()->count() >= self::MAX_PHOTOS_PER_GUEST) {
            return back()->withErrors(['photo' => 'Vous avez atteint le nombre maximum de photos.']);
        }

        $guest->photos()->create([
            'event_id' => $guest->event_id,
            'path' => $request->file('photo')->store("photos/{$guest->event_id}", 'local'),
            'caption' => $request->validated('caption'),
        ]);

        return back()->with('contribution_saved', 'photo');
    }
}
