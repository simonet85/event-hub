<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Guest;
use App\Services\GuestDataEraser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class GuestDataController extends Controller
{
    /**
     * Un invité demande la suppression de ses données : l'hôte, responsable du traitement, la voit et l'effectue.
     * Redemander ne change pas la date de la première demande.
     */
    public function request(Guest $guest): RedirectResponse
    {
        if ($guest->erasure_requested_at === null) {
            $guest->forceFill(['erasure_requested_at' => now()])->save();
        }

        return back()->with('erasure_requested', true);
    }

    /**
     * L'hôte supprime les données d'un invité (à sa demande ou de sa propre initiative). Son lien cesse de fonctionner.
     */
    public function erase(Event $event, Guest $guest, GuestDataEraser $eraser): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($guest->event_id === $event->id, 404);

        $eraser->erase($guest);

        return back()->with('guest_erased', true);
    }

    /**
     * L'hôte supprime les données de tous les invités, une fois l'événement passé (sans attendre la suppression automatique).
     */
    public function purge(Event $event, GuestDataEraser $eraser): RedirectResponse
    {
        Gate::authorize('update', $event);

        abort_unless($event->event_date->isPast(), 422, __("Les données des invités ne peuvent être supprimées qu'après l'événement."));

        $eraser->purge($event);

        return back()->with('guest_data_purged', true);
    }
}
