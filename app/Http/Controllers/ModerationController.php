<?php

namespace App\Http\Controllers;

use App\Enums\ModerationStatus;
use App\Models\Event;
use App\Models\GuestbookEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ModerationController extends Controller
{
    public function index(Event $event): Response
    {
        Gate::authorize('view', $event);

        return Inertia::render('Events/Moderation', [
            'event' => ['title' => $event->title, 'slug' => $event->slug],
            'entries' => $event->guestbookEntries()->with('guest:id,name')->latest()->get()->map(fn (GuestbookEntry $e) => [
                'id' => $e->id,
                'author' => $e->guest->name,
                'message' => $e->message,
                'status' => $e->status->value,
            ]),
        ]);
    }

    public function updateEntry(Request $request, Event $event, GuestbookEntry $entry): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($entry->event_id === $event->id, 404);

        $entry->update(['status' => $this->status($request)]);

        return back();
    }

    public function destroyEntry(Event $event, GuestbookEntry $entry): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($entry->event_id === $event->id, 404);

        $entry->delete();

        return back();
    }

    private function status(Request $request): ModerationStatus
    {
        return ModerationStatus::from($request->validate([
            'status' => ['required', Rule::enum(ModerationStatus::class)],
        ])['status']);
    }
}
