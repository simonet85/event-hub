<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\GuestDataEraser;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('events:purge-guest-data')]
#[Description('Supprime les données personnelles des invités des événements terminés depuis plus longtemps que la durée de conservation')]
class PurgeGuestData extends Command
{
    public function handle(GuestDataEraser $eraser): int
    {
        $limit = now()->subDays(config('privacy.retention_days'));

        $events = Event::query()
            ->whereNull('guest_data_purged_at')
            ->where('event_date', '<', $limit)
            ->get();

        foreach ($events as $event) {
            $count = $eraser->purge($event);
            $this->info("« {$event->title} » : données de {$count} invité(s) supprimées.");
        }

        return self::SUCCESS;
    }
}
