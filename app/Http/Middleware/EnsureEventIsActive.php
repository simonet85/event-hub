<?php

namespace App\Http\Middleware;

use App\Models\Event;
use App\Models\Guest;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Répond 404 aux invités dès que l'événement est archivé (page publique, invitation, RSVP, dons…).
 */
class EnsureEventIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($this->event($request)?->isArchived(), 404);

        return $next($request);
    }

    private function event(Request $request): ?Event
    {
        $subject = $request->route('guest') ?? $request->route('event');

        if ($subject instanceof Guest) {
            return $subject->event;
        }
        if ($subject instanceof Event) {
            return $subject;
        }

        $token = $request->route('token');

        return is_string($token) ? Guest::with('event')->where('token', $token)->first()?->event : null;
    }
}
