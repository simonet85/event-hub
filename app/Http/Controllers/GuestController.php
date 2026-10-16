<?php

namespace App\Http\Controllers;

use App\Enums\RsvpStatus;
use App\Http\Requests\StoreGuestRequest;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GuestController extends Controller
{
    public function index(Event $event): Response
    {
        Gate::authorize('view', $event);

        return Inertia::render('Events/Guests', [
            'event' => [
                'title' => $event->title,
                'slug' => $event->slug,
            ],
            'summary' => $this->summary($event),
            'guests' => $event->guests()->orderBy('name')->get()->map(fn (Guest $guest) => [
                'id' => $guest->id,
                'name' => $guest->name,
                'email' => $guest->email,
                'phone' => $guest->phone,
                'max_companions' => $guest->max_companions,
                'invited_at' => $guest->invited_at?->toIso8601String(),
                'rsvp_status' => $guest->rsvp_status->value,
                'companions_count' => $guest->companions_count,
                'dietary_restrictions' => $guest->dietary_restrictions,
                'favorite_song' => $guest->favorite_song,
                'rsvp_message' => $guest->rsvp_message,
                'invitation_url' => $guest->invitationUrl(),
                'whatsapp_url' => $guest->setRelation('event', $event)->whatsappUrl(),
            ]),
        ]);
    }

    /**
     * @return array{total: int, pending: int, accepted: int, declined: int, headcount: int}
     */
    private function summary(Event $event): array
    {
        $guests = $event->guests()->get(['rsvp_status', 'companions_count']);
        $accepted = $guests->where('rsvp_status', RsvpStatus::Accepted);

        return [
            'total' => $guests->count(),
            'pending' => $guests->where('rsvp_status', RsvpStatus::Pending)->count(),
            'accepted' => $accepted->count(),
            'declined' => $guests->where('rsvp_status', RsvpStatus::Declined)->count(),
            'headcount' => $accepted->count() + $accepted->sum('companions_count'),
        ];
    }

    public function store(StoreGuestRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $data = $request->validated();
        $data['max_companions'] ??= 0;

        $event->guests()->create($data);

        return back();
    }

    public function import(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:1024']]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = null;
        $imported = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) preg_replace('/^\xEF\xBB\xBF/', '', (string) $h))), $row);

                continue;
            }

            $data = array_combine($header, array_pad($row, count($header), null));
            $name = trim((string) ($data['name'] ?? $data['nom'] ?? ''));
            $email = trim((string) ($data['email'] ?? '')) ?: null;

            if ($name === '' || ($email && ! filter_var($email, FILTER_VALIDATE_EMAIL))) {
                continue;
            }

            if ($email && $event->guests()->where('email', $email)->exists()) {
                continue;
            }

            $event->guests()->create([
                'name' => $name,
                'email' => $email,
                'phone' => trim((string) ($data['phone'] ?? $data['telephone'] ?? '')) ?: null,
                'max_companions' => min(10, max(0, (int) ($data['max_companions'] ?? $data['accompagnants'] ?? 0))),
            ]);
            $imported++;
        }

        fclose($handle);

        return back()->with('imported', $imported);
    }

    public function destroy(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($guest->event_id === $event->id, 404);

        $guest->delete();

        return back();
    }
}
