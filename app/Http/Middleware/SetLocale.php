<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Choisit la langue : préférence mémorisée en session, sinon en-tête Accept-Language du navigateur,
 * sinon la langue par défaut de l'application.
 */
class SetLocale
{
    public const SUPPORTED = ['fr', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = $request->getPreferredLanguage(self::SUPPORTED) ?? config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
