<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        @isset($meta)
            {{-- Aperçu de lien : lu par les robots (WhatsApp, messageries) qui n'exécutent pas le JavaScript. --}}
            <meta name="robots" content="noindex, nofollow, noarchive">
            <meta name="description" content="{{ $meta['description'] }}">
            <meta property="og:type" content="website">
            <meta property="og:site_name" content="{{ config('app.name') }}">
            <meta property="og:locale" content="fr_FR">
            <meta property="og:title" content="{{ $meta['title'] }}">
            <meta property="og:description" content="{{ $meta['description'] }}">
            <meta property="og:url" content="{{ $meta['url'] }}">
            <meta property="og:image" content="{{ $meta['image'] }}">
            <meta property="og:image:type" content="image/png">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $meta['title'] }}">
            <meta name="twitter:image" content="{{ $meta['image'] }}">
        @endisset

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
