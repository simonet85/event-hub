<?php

namespace App\Providers;

use App\Models\Guest;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Derrière le serveur d'hébergement, le schéma vu par PHP peut être « http » : les URL absolues (retour et webhook
        // MoneyFusion, image de partage, liens d'invitation) doivent rester en HTTPS.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Livre d'or : limites par invité (jeton) plutôt que par adresse IP seule, car les invités d'une même
        // salle partagent souvent la même connexion ; l'IP garde un plafond large contre les envois en rafale.
        RateLimiter::for('guest-contributions', function (Request $request): array {
            $guest = $request->route('guest');
            $token = $guest instanceof Guest ? $guest->token : (string) $guest;
            $route = (string) $request->route()?->getName();

            return [
                Limit::perMinute(5)->by("{$route}:minute:{$token}"),
                Limit::perHour(40)->by("{$route}:hour:{$token}"),
                Limit::perMinute(120)->by("{$route}:ip:{$request->ip()}"),
            ];
        });
    }
}
