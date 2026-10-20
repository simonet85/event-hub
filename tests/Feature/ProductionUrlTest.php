<?php

use App\Providers\AppServiceProvider;
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
