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
     * Un invité supprime ses propres données depuis son invitation. Son lien cesse ensuite de fonctionner.
     */
    public function erase(Guest $guest, GuestDataEraser $eraser): RedirectResponse
    {
        $event = $guest->event;

        $eraser->erase($guest);

        return redirect()->route('events.public', $event)->with('data_erased', true);
    }

    /**
     * L'hôte supprime les données de tous les invités, une fois l'événement passé (sans attendre la suppression automatique).
     */
    public function purge(Event $event, GuestDataEraser $eraser): RedirectResponse
    {
        Gate::authorize('update', $event);

        abort_unless($event->event_date->isPast(), 422, 'Les données des invités ne peuvent être supprimées qu\'après l\'événement.');

        $eraser->purge($event);

        return back()->with('guest_data_purged', true);
    }
}
