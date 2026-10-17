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
            'cagnotte' => $event->cagnotte_enabled ? $report->cagnotte() : null,
            'moderation' => $report->moderationQueue(),
        ]);
    }
}
