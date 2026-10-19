<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Lien de lecture seule pour le traiteur, créé et révoqué par l'hôte. Créer un lien remplace le précédent.
 */
class CatererLinkController extends Controller
{
    /** Durées proposées à l'hôte, en jours. */
    public const DURATIONS = [1, 3, 7, 14, 30];

    public function store(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $data = $request->validate(['days' => ['required', 'integer', Rule::in(self::DURATIONS)]]);

        $event->caterer_token = Str::random(48);
        $event->caterer_link_expires_at = now()->addDays((int) $data['days']);
        $event->save();

        return back();
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $event->caterer_token = null;
        $event->caterer_link_expires_at = null;
        $event->save();

        return back();
    }
}
