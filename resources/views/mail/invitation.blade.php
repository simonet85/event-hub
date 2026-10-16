<x-mail::message>
# {{ $event->title }}

Bonjour {{ $guest->name }},

Vous êtes invité(e) à **{{ $event->title }}** le {{ $event->event_date->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm') }}@if ($event->location_name), à {{ $event->location_name }}@endif.

<x-mail::button :url="$url">
Voir mon invitation
</x-mail::button>

À très bientôt,<br>
{{ config('app.name') }}
</x-mail::message>
