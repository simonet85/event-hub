{{ $event->title }}
{{ $event->type->label() }}

Bonjour {{ $guest->name }},

Vous êtes invité(e) le {{ $event->event_date->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm') }}@if ($event->location_name), à {{ $event->location_name }}@endif.

Voir mon invitation : {{ $url }}

Ce lien est personnel : ne le transmettez pas. Vous pouvez y confirmer votre présence et, à tout moment, supprimer vos données.

{{ config('app.name') }}
