@php
    use App\Support\Color;

    $theme = $event->resolvedTheme();
    $paper = Color::isHex($theme['secondary_color']) ? $theme['secondary_color'] : '#fbf6ee';
    $accent = Color::isHex($theme['primary_color']) ? $theme['primary_color'] : '#4e6b48';
    $accentText = Color::readableAccent($accent, $paper);
    $onAccent = Color::onAccent($accent);
    $ink = Color::INK;
    $muted = '#66554a';
    $serif = "'Cormorant Garamond', Georgia, 'Times New Roman', serif";
    $sans = "'DM Sans', Arial, Helvetica, sans-serif";
    $date = $event->event_date->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $event->title }}</title>
</head>
<body style="margin:0;padding:0;background:{{ $paper }};">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $paper }};">
    <tr>
        <td align="center" style="padding:32px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #d9c9b0;border-top:4px solid {{ $accent }};">
                <tr>
                    <td style="padding:36px 32px 8px;text-align:center;font-family:{{ $sans }};">
                        <p style="margin:0;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:{{ $muted }};">{{ $event->type->label() }}</p>
                        <h1 style="margin:14px 0 6px;font-family:{{ $serif }};font-size:36px;line-height:1.15;font-weight:500;color:{{ $ink }};">{{ $event->title }}</h1>
                        <p style="margin:0;font-size:18px;color:{{ $accentText }};" aria-hidden="true">✦</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 32px 8px;font-family:{{ $sans }};font-size:16px;line-height:1.6;color:{{ $ink }};text-align:center;">
                        <p style="margin:0 0 16px;">Bonjour {{ $guest->name }},</p>
                        <p style="margin:0 0 4px;">Vous êtes invité(e) le</p>
                        <p style="margin:0;font-family:{{ $serif }};font-size:22px;color:{{ $ink }};">{{ $date }}</p>
                        @if ($event->location_name)
                            <p style="margin:6px 0 0;color:{{ $muted }};">{{ $event->location_name }}</p>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:24px 32px 8px;">
                        <a href="{{ $url }}" style="display:inline-block;padding:14px 28px;background:{{ $accent }};color:{{ $onAccent }};font-family:{{ $sans }};font-size:14px;font-weight:700;letter-spacing:1px;text-transform:uppercase;text-decoration:none;border-radius:2px;">Voir mon invitation</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:16px 32px 32px;font-family:{{ $sans }};font-size:13px;line-height:1.5;color:{{ $muted }};text-align:center;">
                        <p style="margin:0 0 8px;">Ce lien est personnel : ne le transmettez pas. Vous pouvez y confirmer votre présence et, à tout moment, supprimer vos données.</p>
                        <p style="margin:0;">Si le bouton ne s'affiche pas : <a href="{{ $url }}" style="color:{{ $accentText }};word-break:break-all;">{{ $url }}</a></p>
                    </td>
                </tr>
            </table>
            <p style="margin:16px 0 0;font-family:{{ $sans }};font-size:12px;color:{{ $muted }};">{{ config('app.name') }}</p>
        </td>
    </tr>
</table>
</body>
</html>
