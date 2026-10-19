@php
    use App\Support\Color;

    $theme = $event->resolvedTheme();
    $paper = Color::isHex($theme['secondary_color']) ? $theme['secondary_color'] : '#fbf6ee';
    $accent = Color::isHex($theme['primary_color']) ? $theme['primary_color'] : '#4e6b48';
    $accentText = Color::readableAccent($accent, '#ffffff');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        /* Impression : marges réservées au pied de page, fond blanc (économie d'encre), couleurs du thème de l'événement. */
        @page { margin: 24mm 16mm 22mm 16mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.4; color: #2a1f17; background: #ffffff; }
        .band { height: 5px; background: {{ $accent }}; margin-bottom: 12px; }
        h1 { font-size: 22px; margin: 0 0 2px; color: {{ $accentText }}; }
        h2 { font-size: 14px; margin: 22px 0 6px; border-bottom: 1px solid {{ $accent }}; padding-bottom: 3px; page-break-after: avoid; }
        .meta { color: #66554a; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { text-align: left; background: {{ $paper }}; }
        th, td { padding: 4px 6px; border: 1px solid #d9c9b0; vertical-align: top; }
        tr { page-break-inside: avoid; }
        thead { display: table-header-group; }
        .kpi td { text-align: center; font-size: 15px; font-weight: bold; }
        .kpi small { display: block; font-size: 9px; font-weight: normal; color: #66554a; }
        .muted { color: #66554a; }
        .table-block { page-break-inside: avoid; }
        .footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 9px; color: #66554a; border-top: 1px solid #d9c9b0; padding-top: 4px; }
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>
    <div class="footer">{{ $event->title }} — édité le {{ now()->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm') }} — page <span class="page"></span></div>

    <div class="band"></div>
    <h1>@yield('title')</h1>
    <p class="meta">{{ $event->title }} — {{ $event->event_date->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm') }}@if ($event->location_name) — {{ $event->location_name }}@endif</p>
    @yield('content')
</body>
</html>
