<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    // Géocodage des adresses (carte des événements) via Nominatim / OpenStreetMap.
    // Respectez leur politique : User-Agent identifiable, 1 requête par seconde.
    'geocoding' => [
        'enabled' => (bool) env('GEOCODING_ENABLED', true),
        'delay_ms' => (int) env('GEOCODING_DELAY_MS', 1100),
        'url' => env('GEOCODING_URL', 'https://nominatim.openstreetmap.org/search'),
        'user_agent' => env('GEOCODING_USER_AGENT', 'EventHub/1.0 (+https://github.com/simonet85/event-hub)'),
    ],

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
