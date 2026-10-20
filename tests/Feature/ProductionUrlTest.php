<?php

use App\Providers\AppServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\URL;

test('en production, les URL absolues sont en HTTPS même si la requête arrive en HTTP', function () {
    URL::forceRootUrl('http://exemple.test');
    URL::forceScheme('http');

    $this->app->detectEnvironment(fn () => 'production');
    (new AppServiceProvider($this->app))->boot();

    expect(route('webhooks.moneyfusion'))->toStartWith('https://')
        ->and(url('/payment'))->toStartWith('https://');
});

test('hors production, le schéma n\'est pas forcé', function () {
    URL::forceRootUrl('http://exemple.test');
    URL::forceScheme('http');

    $this->app->detectEnvironment(fn () => 'local');
    (new AppServiceProvider($this->app))->boot();

    expect(route('webhooks.moneyfusion'))->toStartWith('http://');
});

test('en production, le planificateur vide la file chaque minute (hébergement sans worker permanent)', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'queue:work'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        ->and($event->environments)->toBe(['production'])
        ->and($event->command)->toContain('--stop-when-empty')->toContain('--max-time=55')
        ->and($event->withoutOverlapping)->toBeTrue();
});
