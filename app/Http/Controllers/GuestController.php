<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGuestRequest;
use App\Models\Event;
use App\Models\Guest;
use App\Services\EventReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'summary' => (new EventReport($event))->rsvp(),
            'guests' => $event->guests()->orderBy('name')->orderBy('id')->paginate(50)->through(fn (Guest $guest) => [
                'id' => $guest->id,
                'name' => $guest->name,
                'email' => $guest->email,
                'phone' => $guest->phone,
                'max_companions' => $guest->max_companions,
                'invited_at' => $guest->invited_at?->toIso8601String(),
                'table_name' => $guest->table_name,
                'rsvp_status' => $guest->rsvp_status->value,
                'companions_count' => $guest->companions_count,
                'dietary_restrictions' => $guest->dietary_restrictions,
                'favorite_song' => $guest->favorite_song,
                'rsvp_message' => $guest->rsvp_message,
                'erasure_requested' => $guest->erasure_requested_at !== null,
                'erased' => $guest->erased_at !== null,
                'invitation_url' => $guest->invitationUrl(),
                'whatsapp_url' => $guest->setRelation('event', $event)->whatsappUrl(),
            ]),
        ]);
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

        try {
            $imported = DB::transaction(fn () => $this->importRows($event, $handle));
        } finally {
            fclose($handle);
        }

        return back()->with('imported', $imported);
    }

    /**
     * @param  resource  $handle
     */
    private function importRows(Event $event, $handle): int
    {
        $header = null;
        $imported = 0;

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            if ($header === null) {
                $header = array_map(fn ($h) => strtolower(trim((string) preg_replace('/^ï»¿/', '', (string) $h))), $row);

                continue;
            }

            $data = array_combine($header, array_slice(array_pad($row, count($header), null), 0, count($header)));
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

        return $imported;
    }

    public function update(Request $request, Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($guest->event_id === $event->id, 404);

        $data = $request->validate(['table_name' => ['nullable', 'string', 'max:100']]);

        $guest->forceFill(['table_name' => filled($data['table_name'] ?? null) ? trim($data['table_name']) : null])->save();

        return back();
    }

    public function destroy(Event $event, Guest $guest): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($guest->event_id === $event->id, 404);

        $guest->delete();

        return back();
    }
}
