<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Http\Requests\StoreEventRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Events/Index', [
            'events' => $request->user()->events()->orderBy('event_date')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Events/Create', [
            'types' => collect(EventType::cases())->map(fn (EventType $t) => [
                'value' => $t->value,
                'label' => $t->label(),
                'theme' => $t->defaultTheme(),
            ])->all(),
        ]);
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

    public function showPublic(Event $event): Response
    {
        return Inertia::render('Events/ShowPublic', [
            'event' => [
                'title' => $event->title,
                'slug' => $event->slug,
                'type' => $event->type->value,
                'type_label' => $event->type->label(),
                'event_date' => $event->event_date->toIso8601String(),
                'location_name' => $event->location_name,
                'location_address' => $event->location_address,
                'welcome_message' => $event->welcome_message,
                'theme_config' => $event->theme_config,
            ],
        ]);
    }
}
