<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Polices auto-hébergées (@font-face dans resources/css/app.css) : on précharge les deux utilisées au premier affichage. -->
        <link rel="preload" href="/fonts/dm-sans-normal-latin.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/cormorant-garamond-normal-latin.woff2" as="font" type="font/woff2" crossorigin>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.ts', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
