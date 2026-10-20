<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('events:purge-guest-data')->daily()->withoutOverlapping();
Schedule::command('contributions:reconcile')->everyTenMinutes()->withoutOverlapping();

// Hébergement mutualisé : pas de processus permanent. Le cron appelle schedule:run chaque minute, qui vide la file
// (e-mails d'invitation, géolocalisation, vérification des paiements) puis s'arrête. En local, on lance queue:work soi-même.
Schedule::command('queue:work --stop-when-empty --tries=2 --max-time=55')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->environments(['production']);
