@php
    use App\Support\Color;

    // Gabarit d'e-mail : styles en ligne et tableaux uniquement (les clients de messagerie ignorent les variables CSS et le flex).
    $theme = $event->resolvedTheme();
    $paper = Color::isHex($theme['secondary_color']) ? $theme['secondary_color'] : '#fbf6ee';
    $accent = Color::isHex($theme['primary_color']) ? $theme['primary_color'] : '#4e6b48';
    $accentText = Color::readableAccent($accent, '#fffdf9');
    $onAccent = Color::onAccent($accent);
    $ink = Color::INK;
    $muted = '#66554a';
    $line = '#e4d6bf';
    $panel = '#faf5ea';
    $serif = "'Cormorant Garamond', Georgia, 'Times New Roman', serif";
    $sans = "'DM Sans', Arial, Helvetica, sans-serif";
    $date = $event->event_date->locale('fr')->isoFormat('dddd D MMMM YYYY');
    $time = $event->event_date->locale('fr')->isoFormat('HH [h] mm');
    $preheader = "Vous êtes invité(e) le {$date} à {$time}".($event->location_name ? ", {$event->location_name}" : '').'.';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>{{ $event->title }}</title>
    <style>
        @media only screen and (max-width: 480px) {
            .stack { display: block !important; width: 100% !important; box-sizing: border-box; border-left: 0 !important; }
            .stack + .stack { border-top: 1px solid {{ $line }} !important; }
            .hero-title { font-size: 34px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:{{ $paper }};-webkit-text-size-adjust:100%;">
{{-- Aperçu affiché par la boîte de réception à côté de l'objet --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;font-size:1px;line-height:1px;color:{{ $paper }};">{{ $preheader }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $paper }};">
    <tr>
        <td align="center" style="padding:32px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fffdf9;border:1px solid {{ $line }};border-radius:6px;overflow:hidden;">
                {{-- Bandeau : titre de l'événement sur la couleur d'accent --}}
                <tr>
                    <td align="center" bgcolor="{{ $accent }}" style="background:{{ $accent }};padding:44px 32px 36px;font-family:{{ $sans }};">
                        <p style="margin:0;font-size:12px;letter-spacing:3px;text-transform:uppercase;color:{{ $onAccent }};opacity:0.85;">{{ $event->type->label() }}</p>
                        <h1 class="hero-title" style="margin:16px 0 14px;font-family:{{ $serif }};font-size:42px;line-height:1.1;font-weight:500;color:{{ $onAccent }};">{{ $event->title }}</h1>
                        <p style="margin:0;font-size:16px;letter-spacing:6px;color:{{ $onAccent }};" aria-hidden="true">&mdash; ✦ &mdash;</p>
                    </td>
                </tr>
                {{-- Salutation --}}
                <tr>
                    <td align="center" style="padding:36px 32px 8px;font-family:{{ $sans }};font-size:16px;line-height:1.6;color:{{ $ink }};">
                        <p style="margin:0 0 6px;font-family:{{ $serif }};font-size:26px;color:{{ $ink }};">Bonjour {{ $guest->name }},</p>
                        <p style="margin:0;color:{{ $muted }};">Nous serions heureux de vous compter parmi nous.</p>
                    </td>
                </tr>
                {{-- Date et lieu --}}
                <tr>
                    <td style="padding:24px 32px 8px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $panel }};border:1px solid {{ $line }};border-radius:4px;">
                            <tr>
                                <td class="stack" width="{{ $event->location_name ? '50%' : '100%' }}" valign="top" align="center" style="padding:20px 16px;font-family:{{ $sans }};">
                                    <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $muted }};">Date</p>
                                    <p style="margin:0;font-family:{{ $serif }};font-size:20px;line-height:1.3;color:{{ $ink }};">{{ ucfirst($date) }}</p>
                                    <p style="margin:4px 0 0;font-size:14px;color:{{ $accentText }};">à {{ $time }}</p>
                                </td>
                                @if ($event->location_name)
                                    <td class="stack" width="50%" valign="top" align="center" style="padding:20px 16px;border-left:1px solid {{ $line }};font-family:{{ $sans }};">
                                        <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $muted }};">Lieu</p>
                                        <p style="margin:0;font-family:{{ $serif }};font-size:20px;line-height:1.3;color:{{ $ink }};">{{ $event->location_name }}</p>
                                        @if ($address)
                                            <p style="margin:4px 0 0;font-size:13px;line-height:1.4;color:{{ $muted }};">{{ $address }}</p>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        </table>
                    </td>
                </tr>
                @if ($event->dress_code)
                    <tr>
                        <td align="center" style="padding:12px 32px 0;font-family:{{ $sans }};font-size:14px;color:{{ $muted }};">
                            Tenue souhaitée : <strong style="color:{{ $ink }};">{{ $event->dress_code }}</strong>
                        </td>
                    </tr>
                @endif
                @if ($event->welcome_message)
                    <tr>
                        <td style="padding:20px 32px 0;">
                            <p style="margin:0;padding:4px 0 4px 16px;border-left:3px solid {{ $accent }};font-family:{{ $serif }};font-size:19px;font-style:italic;line-height:1.5;color:{{ $ink }};">{{ $event->welcome_message }}</p>
                        </td>
                    </tr>
                @endif
                {{-- Appel à l'action --}}
                <tr>
                    <td align="center" style="padding:32px 32px 8px;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" bgcolor="{{ $accent }}" style="background:{{ $accent }};border-radius:3px;">
                                    <a href="{{ $url }}" style="display:inline-block;padding:16px 36px;font-family:{{ $sans }};font-size:14px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;color:{{ $onAccent }};">Voir mon invitation</a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:14px 0 0;font-family:{{ $sans }};font-size:13px;color:{{ $muted }};">Confirmez votre présence en un clic.</p>
                    </td>
                </tr>
                {{-- Pied de carte --}}
                <tr>
                    <td style="padding:24px 32px 32px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $line }};">
                            <tr>
                                <td align="center" style="padding-top:20px;font-family:{{ $sans }};font-size:12px;line-height:1.6;color:{{ $muted }};">
                                    <p style="margin:0 0 8px;">Ce lien est personnel : ne le transmettez pas. Vous pouvez y confirmer votre présence et, à tout moment, supprimer vos données.</p>
                                    <p style="margin:0;">Si le bouton ne s'affiche pas : <a href="{{ $url }}" style="color:{{ $accentText }};word-break:break-all;">{{ $url }}</a></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <p style="margin:18px 0 0;font-family:{{ $sans }};font-size:12px;letter-spacing:2px;text-transform:uppercase;color:{{ $muted }};"><span aria-hidden="true">✦</span> {{ config('app.name') }}</p>
        </td>
    </tr>
</table>
</body>
</html>
