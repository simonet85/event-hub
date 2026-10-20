<?php

namespace App\Http\Controllers;

use App\Http\Requests\EventDetailsRequest;
use App\Http\Requests\ScheduleItemRequest;
use App\Models\Event;
use App\Models\ScheduleItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public const MAX_ITEMS = 100;

    public function index(Event $event): Response
    {
        Gate::authorize('view', $event);

        return Inertia::render('Events/Schedule', [
            'event' => [
                'title' => $event->title,
                'slug' => $event->slug,
                'event_date' => $event->event_date->toIso8601String(),
                'dress_code' => $event->dress_code,
                'latitude' => $event->latitude,
                'longitude' => $event->longitude,
            ],
            'items' => $event->scheduleItems()->get()->map(fn (ScheduleItem $item) => [
                'id' => $item->id,
                'starts_at' => $item->starts_at->toIso8601String(),
                'title' => $item->title,
                'description' => $item->description,
            ]),
        ]);
    }

    public function store(ScheduleItemRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        if ($event->scheduleItems()->count() >= self::MAX_ITEMS) {
            return back()->withErrors(['title' => __('Le programme est limité à :max étapes.', ['max' => self::MAX_ITEMS])]);
        }

        $event->scheduleItems()->create($request->validated());

        return back();
    }

    public function destroy(Event $event, ScheduleItem $item): RedirectResponse
    {
        Gate::authorize('update', $event);
        abort_unless($item->event_id === $event->id, 404);

        $item->delete();

        return back();
    }

    public function updateDetails(EventDetailsRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $event->update($request->validated());

        return back()->with('details_saved', true);
    }
}
