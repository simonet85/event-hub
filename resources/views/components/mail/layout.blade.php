@props(['theme', 'title', 'eyebrow' => null, 'preheader' => '', 'footer' => null, 'appName' => null])
{{-- Coque commune des e-mails : bandeau aux couleurs de l'événement, carte centrale, pied. Styles en ligne et tableaux uniquement. --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>{{ $title }}</title>
    <style>
        @media only screen and (max-width: 480px) {
            .stack { display: block !important; width: 100% !important; box-sizing: border-box; border-left: 0 !important; }
            .stack + .stack { border-top: 1px solid {{ $theme->line }} !important; }
            .hero-title { font-size: 34px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:{{ $theme->paper }};-webkit-text-size-adjust:100%;">
{{-- Aperçu affiché par la boîte de réception à côté de l'objet --}}
<div style="display:none;max-height:0;overflow:hidden;opacity:0;font-size:1px;line-height:1px;color:{{ $theme->paper }};">{{ $preheader }}&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;&#847;&zwnj;&nbsp;</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $theme->paper }};">
    <tr>
        <td align="center" style="padding:32px 12px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#fffdf9;border:1px solid {{ $theme->line }};border-radius:6px;overflow:hidden;">
                <tr>
                    <td align="center" bgcolor="{{ $theme->accent }}" style="background:{{ $theme->accent }};padding:44px 32px 36px;font-family:{{ $theme->sans }};">
                        @if ($eyebrow)
                            <p style="margin:0;font-size:12px;letter-spacing:3px;text-transform:uppercase;color:{{ $theme->onAccent }};opacity:0.85;">{{ $eyebrow }}</p>
                        @endif
                        <h1 class="hero-title" style="margin:16px 0 14px;font-family:{{ $theme->serif }};font-size:42px;line-height:1.1;font-weight:500;color:{{ $theme->onAccent }};">{{ $title }}</h1>
                        <p style="margin:0;font-size:16px;letter-spacing:6px;color:{{ $theme->onAccent }};" aria-hidden="true">&mdash; ✦ &mdash;</p>
                    </td>
                </tr>
                {{ $slot }}
                @if (trim((string) $footer) !== '')
                    <tr>
                        <td style="padding:24px 32px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid {{ $theme->line }};">
                                <tr>
                                    <td align="center" style="padding-top:20px;font-family:{{ $theme->sans }};font-size:12px;line-height:1.6;color:{{ $theme->muted }};">{{ $footer }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif
            </table>
            <p style="margin:18px 0 0;font-family:{{ $theme->sans }};font-size:12px;letter-spacing:2px;text-transform:uppercase;color:{{ $theme->muted }};"><span aria-hidden="true">✦</span> {{ $appName ?? config('app.name') }}</p>
        </td>
    </tr>
</table>
</body>
</html>
