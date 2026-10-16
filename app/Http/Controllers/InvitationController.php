<?php

namespace App\Http\Controllers;

use App\Jobs\SendInvitation;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Invitation personnalisée d'un invité, accessible sans compte via son jeton.
     */
    public function show(string $token): Response
    {
        $guest = Guest::with('event')->where('token', $token)->firstOrFail();
        $event = $guest->event;

        return Inertia::render('Invitations/Show', [
            'token' => $guest->token,
            'guest' => [
                'name' => $guest->name,
                'max_companions' => $guest->max_companions,
                'url' => $guest->invitationUrl(),
                'rsvp_status' => $guest->rsvp_status->value,
                'companions_count' => $guest->companions_count,
                'dietary_restrictions' => $guest->dietary_restrictions,
                'favorite_song' => $guest->favorite_song,
                'rsvp_message' => $guest->rsvp_message,
            ],
            'event' => $event->load('scheduleItems')->publicPayload(),
        ]);
    }

    /**
     * Envoie par email les invitations pas encore envoyées.
     */
    public function sendAll(Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $guests = $event->guests()->whereNotNull('email')->whereNull('invited_at')->get();

        foreach ($guests as $guest) {
            SendInvitation::dispatch($guest);
        }

        return back()->with('sent', $guests->count());
    }

    public function send(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($guest->event_id === $event->id, 404);

        if (! $guest->email) {
            return back()->withErrors(['email' => 'Cet invité n\'a pas d\'adresse email.']);
        }

        SendInvitation::dispatch($guest, resend: true);

        return back()->with('sent', 1);
    }
}
