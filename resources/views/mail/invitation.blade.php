@php
    use App\Support\MailTheme;

    $t = MailTheme::for($event);
    $date = $event->event_date->locale('fr')->isoFormat('dddd D MMMM YYYY');
    $time = $event->event_date->locale('fr')->isoFormat('HH [h] mm');
    $preheader = "Vous êtes invité(e) le {$date} à {$time}".($event->location_name ? ", {$event->location_name}" : '').'.';
@endphp
<x-mail.layout :theme="$t" :title="$event->title" :eyebrow="$event->type->label()" :preheader="$preheader">
    {{-- Salutation --}}
    <tr>
        <td align="center" style="padding:36px 32px 8px;font-family:{{ $t->sans }};font-size:16px;line-height:1.6;color:{{ $t->ink }};">
            <p style="margin:0 0 6px;font-family:{{ $t->serif }};font-size:26px;color:{{ $t->ink }};">Bonjour {{ $guest->name }},</p>
            <p style="margin:0;color:{{ $t->muted }};">Nous serions heureux de vous compter parmi nous.</p>
        </td>
    </tr>
    {{-- Date et lieu --}}
    <tr>
        <td style="padding:24px 32px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $t->panel }};border:1px solid {{ $t->line }};border-radius:4px;">
                <tr>
                    <td class="stack" width="{{ $event->location_name ? '50%' : '100%' }}" valign="top" align="center" style="padding:20px 16px;font-family:{{ $t->sans }};">
                        <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $t->muted }};">Date</p>
                        <p style="margin:0;font-family:{{ $t->serif }};font-size:20px;line-height:1.3;color:{{ $t->ink }};">{{ ucfirst($date) }}</p>
                        <p style="margin:4px 0 0;font-size:14px;color:{{ $t->accentText }};">à {{ $time }}</p>
                    </td>
                    @if ($event->location_name)
                        <td class="stack" width="50%" valign="top" align="center" style="padding:20px 16px;border-left:1px solid {{ $t->line }};font-family:{{ $t->sans }};">
                            <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $t->muted }};">Lieu</p>
                            <p style="margin:0;font-family:{{ $t->serif }};font-size:20px;line-height:1.3;color:{{ $t->ink }};">{{ $event->location_name }}</p>
                            @if ($address)
                                <p style="margin:4px 0 0;font-size:13px;line-height:1.4;color:{{ $t->muted }};">{{ $address }}</p>
                            @endif
                        </td>
                    @endif
                </tr>
            </table>
        </td>
    </tr>
    @if ($event->dress_code)
        <tr>
            <td align="center" style="padding:12px 32px 0;font-family:{{ $t->sans }};font-size:14px;color:{{ $t->muted }};">
                Tenue souhaitée : <strong style="color:{{ $t->ink }};">{{ $event->dress_code }}</strong>
            </td>
        </tr>
    @endif
    @if ($event->welcome_message)
        <tr>
            <td style="padding:20px 32px 0;">
                <p style="margin:0;padding:4px 0 4px 16px;border-left:3px solid {{ $t->accent }};font-family:{{ $t->serif }};font-size:19px;font-style:italic;line-height:1.5;color:{{ $t->ink }};">{{ $event->welcome_message }}</p>
            </td>
        </tr>
    @endif
    {{-- Appel à l'action --}}
    <tr>
        <td align="center" style="padding:32px 32px 8px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" bgcolor="{{ $t->accent }}" style="background:{{ $t->accent }};border-radius:3px;">
                        <a href="{{ $url }}" style="display:inline-block;padding:16px 36px;font-family:{{ $t->sans }};font-size:14px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;color:{{ $t->onAccent }};">Voir mon invitation</a>
                    </td>
                </tr>
            </table>
            <p style="margin:14px 0 0;font-family:{{ $t->sans }};font-size:13px;color:{{ $t->muted }};">Confirmez votre présence en un clic.</p>
        </td>
    </tr>
    <x-slot:footer>
        <p style="margin:0 0 8px;">Ce lien est personnel : ne le transmettez pas. Vous pouvez y confirmer votre présence et, à tout moment, supprimer vos données.</p>
        <p style="margin:0;">Si le bouton ne s'affiche pas : <a href="{{ $url }}" style="color:{{ $t->accentText }};word-break:break-all;">{{ $url }}</a></p>
    </x-slot:footer>
</x-mail.layout>
