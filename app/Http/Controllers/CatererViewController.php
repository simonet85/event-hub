<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\EventReport;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Vue traiteur en lecture seule. Elle ne montre que ce qu'il faut pour préparer le service (couverts, tables,
 * régimes) : pas de coordonnées, pas de message, et les noms sont abrégés (« Awa K. »).
 */
class CatererViewController extends Controller
{
    public function show(string $token): Response
    {
        $event = Event::query()->where('caterer_token', $token)->whereNull('archived_at')->first();

        // Jeton inconnu, révoqué ou événement archivé : 404 identique. Lien expiré : 410, pour que le traiteur sache qu'il doit en demander un nouveau.
        abort_if($event === null, 404);
        abort_if(! $event->hasActiveCatererLink(), 410, __("Ce lien a expiré. Demandez un nouveau lien à l'organisateur."));

        $report = new EventReport($event);
        $rsvp = $report->rsvp();

        return Inertia::render('Caterer/Show', [
            'event' => [
                'title' => $event->title,
                'type_label' => __($event->type->label()),
                'event_date' => $event->event_date->toIso8601String(),
                'location_name' => $event->location_name,
            ],
            'expires_at' => $event->caterer_link_expires_at->toIso8601String(),
            'counts' => [
                'headcount' => $rsvp['headcount'],
                'accepted' => $rsvp['accepted'],
                'pending' => $rsvp['pending'],
                'declined' => $rsvp['declined'],
            ],
            'tables' => $report->seating()->map(fn (array $row) => [
                'table' => $row['table'],
                'headcount' => $row['headcount'],
            ])->all(),
            'dietary' => $report->dietary()->map(fn (array $row) => [
                'guest' => self::shortName($row['guest']),
                'table' => $row['table'],
                'restriction' => $row['restriction'],
                'seats' => $row['seats'],
            ])->all(),
        ]);
    }

    /** « Awa Koné » devient « Awa K. » : le traiteur n'a pas besoin du nom complet. */
    public static function shortName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($parts) < 2) {
            return $parts[0] ?? '';
        }

        return $parts[0].' '.mb_strtoupper(mb_substr(end($parts), 0, 1)).'.';
    }
}
