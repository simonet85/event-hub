<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Conservation des données des invités
    |--------------------------------------------------------------------------
    |
    | Nombre de jours après la date de l'événement au bout desquels les données
    | personnelles des invités (coordonnées, régimes, messages, photos) sont
    | supprimées automatiquement. Les montants des contributions sont conservés.
    |
    */

    'retention_days' => (int) env('GUEST_DATA_RETENTION_DAYS', 90),

];
