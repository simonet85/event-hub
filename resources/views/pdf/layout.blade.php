<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f2937; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 14px; margin: 22px 0 6px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }
        .meta { color: #6b7280; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { text-align: left; background: #f3f4f6; }
        th, td { padding: 4px 6px; border: 1px solid #e5e7eb; vertical-align: top; }
        .kpi td { text-align: center; font-size: 15px; font-weight: bold; }
        .kpi small { display: block; font-size: 9px; font-weight: normal; color: #6b7280; }
        .muted { color: #6b7280; }
        .table-block { page-break-inside: avoid; }
    </style>
</head>
<body>
    <h1>@yield('title')</h1>
    <p class="meta">{{ $event->title }} — {{ $event->event_date->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm') }}@if ($event->location_name) — {{ $event->location_name }}@endif</p>
    @yield('content')
    <p class="muted" style="margin-top:24px">Édité le {{ now()->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm') }}</p>
</body>
</html>
