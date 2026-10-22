{{ $event->title }}
Reçu de contribution

Merci {{ $guest->name }} !

Votre contribution a bien été reçue et confirmée.

Montant : {{ $amount }}
Destination : {{ $target }}
Date : {{ $paidAt }}
Référence : N° {{ $contribution->id }}
@if ($contribution->message)

Votre petit mot : « {{ $contribution->message }} »
@endif

Revoir mon invitation : {{ $url }}

Ce message confirme votre participation à « {{ $event->title }} ». Il ne constitue pas un reçu fiscal.

{{ config('app.name') }}
