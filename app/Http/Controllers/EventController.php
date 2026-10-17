<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        $archived = $request->boolean('archived');
        $query = $request->user()->events()->orderBy('event_date');

        return Inertia::render('Events/Index', [
            'events' => ($archived ? $query->archived() : $query->active())->paginate(20)->withQueryString(),
            'showingArchived' => $archived,
            'archivedCount' => $request->user()->events()->archived()->count(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Events/Create', [
            'types' => $this->types(),
        ]);
    }

    /** @return list<array{value: string, label: string, theme: array<string, string>}> */
    private function types(): array
    {
        return collect(EventType::cases())->map(fn (EventType $t) => [
            'value' => $t->value,
            'label' => $t->label(),
            'theme' => $t->defaultTheme(),
        ])->all();
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $type = EventType::from($data['type']);

        $data['slug'] = filled($data['slug'] ?? null)
            ? $data['slug']
            : Str::slug($data['title']).'-'.Str::lower(Str::random(6));
        $data['theme_config'] = array_merge($type->defaultTheme(), array_filter($data['theme_config'] ?? []));

        $event = $request->user()->events()->create($data);

        return to_route('events.index')->with('created', $event->slug);
    }

    public function edit(Event $event): Response
    {
        Gate::authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => [
                'title' => $event->title,
                'slug' => $event->slug,
                'type' => $event->type->value,
                'event_date' => $event->event_date->format('Y-m-d\TH:i'),
                'location_name' => $event->location_name,
                'location_address' => $event->location_address,
                'welcome_message' => $event->welcome_message,
                'theme_config' => $event->theme_config,
                'archived' => $event->isArchived(),
            ],
            'types' => $this->types(),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $data = $request->validated();
        $type = EventType::from($data['type']);

        $data['theme_config'] = array_merge(
            $event->theme_config ?? $type->defaultTheme(),
            array_filter($data['theme_config'] ?? []),
        );

        $event->update($data);

        return to_route('dashboard.show', $event)->with('event_updated', true);
    }

    public function archive(Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        if (! $event->isArchived()) {
            $event->archived_at = now();
            $event->save();
        }

        return to_route('events.index')->with('event_archived', $event->title);
    }

    public function restore(Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $event->archived_at = null;
        $event->save();

        return to_route('events.index')->with('event_restored', $event->title);
    }

    public function showPublic(Event $event): Response
    {
        return Inertia::render('Events/ShowPublic', [
            'event' => $event->load('scheduleItems')->publicPayload(),
        ]);
    }
}
