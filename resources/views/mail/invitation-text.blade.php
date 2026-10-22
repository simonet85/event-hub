@php
    $when = $event->event_date->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm');
    $where = $event->location_name
        ? ', à '.$event->location_name.($address ? ' ('.$address.')' : '')
        : '';
@endphp
{{ $event->title }}
{{ $event->type->label() }}

Bonjour {{ $guest->name }},

Vous êtes invité(e) le {{ $when }}{{ $where }}.
@if ($event->dress_code)

Tenue souhaitée : {{ $event->dress_code }}
@endif
@if ($event->welcome_message)

« {{ $event->welcome_message }} »
@endif

Voir mon invitation : {{ $url }}

Ce lien est personnel : ne le transmettez pas. Vous pouvez y confirmer votre présence et, à tout moment, supprimer vos données.

{{ config('app.name') }}
