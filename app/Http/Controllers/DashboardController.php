<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventReport;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(Event $event): Response
    {
        Gate::authorize('view', $event);

        $report = new EventReport($event);

        return Inertia::render('Events/Dashboard', [
            'event' => [
                'title' => $event->title,
                'slug' => $event->slug,
                // Adresse renseignée mais pas (encore) placée sur la carte : recherche en cours ou adresse introuvable.
                'map_missing' => filled($event->location_address) && ($event->latitude === null || $event->longitude === null),
                'event_date' => $event->event_date->toIso8601String(),
                'days_left' => max(0, (int) now()->startOfDay()->diffInDays($event->event_date->copy()->startOfDay(), false)),
            ],
            'rsvp' => $report->rsvp(),
            'dietary' => $report->dietary(),
            'tables' => $report->seating()->map(fn (array $row) => [
                'table' => $row['table'],
                'guests' => $row['guests']->count(),
                'headcount' => $row['headcount'],
            ])->all(),
            'caterer_link' => $event->hasActiveCatererLink() ? [
                'url' => route('caterer.show', $event->caterer_token),
                'expires_at' => $event->caterer_link_expires_at->toIso8601String(),
            ] : null,
            'cagnotte' => $event->cagnotte_enabled ? $report->cagnotte() : null,
            'moderation' => $report->moderationQueue(),
        ]);
    }
}
