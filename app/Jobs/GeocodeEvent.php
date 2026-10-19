<?php

namespace App\Jobs;

use App\Models\Event;
use App\Services\Geocoder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Place un événement sur la carte d'après son adresse, hors de la requête de l'hôte : le géocodage peut
 * enchaîner plusieurs appels réseau espacés d'une seconde (limite de Nominatim).
 */
class GeocodeEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 90;

    /**
     * @var array<int, int>
     */
    public array $backoff = [60];

    public function __construct(public int $eventId, public string $address) {}

    public function handle(Geocoder $geocoder): void
    {
        $event = Event::find($this->eventId);

        // L'hôte a changé l'adresse depuis l'envoi du job (ou supprimé l'événement) : ce résultat serait obsolète.
        if ($event === null || $event->location_address !== $this->address) {
            return;
        }

        $position = $geocoder->locate($this->address, $event->location_name);

        if ($position !== null) {
            $event->forceFill($position)->save();
        }
    }
}
