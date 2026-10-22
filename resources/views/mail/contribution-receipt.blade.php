@php
    use App\Support\MailTheme;

    $t = MailTheme::for($event);
    $preheader = "Votre contribution de {$amount} est confirmée. Merci !";
@endphp
<x-mail.layout :theme="$t" :title="$event->title" eyebrow="Reçu de contribution" :preheader="$preheader">
    <tr>
        <td align="center" style="padding:36px 32px 8px;font-family:{{ $t->sans }};font-size:16px;line-height:1.6;color:{{ $t->ink }};">
            <p style="margin:0 0 6px;font-family:{{ $t->serif }};font-size:26px;color:{{ $t->ink }};">Merci {{ $guest->name }} !</p>
            <p style="margin:0;color:{{ $t->muted }};">Votre contribution a bien été reçue et confirmée.</p>
        </td>
    </tr>
    {{-- Montant --}}
    <tr>
        <td style="padding:24px 32px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $t->panel }};border:1px solid {{ $t->line }};border-radius:4px;">
                <tr>
                    <td align="center" style="padding:24px 16px 8px;font-family:{{ $t->sans }};">
                        <p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $t->muted }};">Montant</p>
                        <p style="margin:0;font-family:{{ $t->serif }};font-size:42px;line-height:1.1;color:{{ $t->accentText }};">{{ $amount }}</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:12px 24px 20px;font-family:{{ $t->sans }};font-size:14px;line-height:1.5;color:{{ $t->ink }};">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="padding:8px 0;border-top:1px solid {{ $t->line }};color:{{ $t->muted }};">Destination</td>
                                <td align="right" style="padding:8px 0;border-top:1px solid {{ $t->line }};">{{ $target }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-top:1px solid {{ $t->line }};color:{{ $t->muted }};">Date</td>
                                <td align="right" style="padding:8px 0;border-top:1px solid {{ $t->line }};">{{ $paidAt }}</td>
                            </tr>
                            <tr>
                                <td style="padding:8px 0;border-top:1px solid {{ $t->line }};color:{{ $t->muted }};">Référence</td>
                                <td align="right" style="padding:8px 0;border-top:1px solid {{ $t->line }};">N° {{ $contribution->id }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    @if ($contribution->message)
        <tr>
            <td style="padding:20px 32px 0;">
                <p style="margin:0 0 6px;font-family:{{ $t->sans }};font-size:11px;letter-spacing:2px;text-transform:uppercase;color:{{ $t->muted }};">Votre petit mot</p>
                <p style="margin:0;padding:4px 0 4px 16px;border-left:3px solid {{ $t->accent }};font-family:{{ $t->serif }};font-size:19px;font-style:italic;line-height:1.5;color:{{ $t->ink }};">{{ $contribution->message }}</p>
            </td>
        </tr>
    @endif
    <tr>
        <td align="center" style="padding:32px 32px 8px;">
            <table role="presentation" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" bgcolor="{{ $t->accent }}" style="background:{{ $t->accent }};border-radius:3px;">
                        <a href="{{ $url }}" style="display:inline-block;padding:16px 36px;font-family:{{ $t->sans }};font-size:14px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;text-decoration:none;color:{{ $t->onAccent }};">Revoir mon invitation</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
    <x-slot:footer>
        <p style="margin:0 0 8px;">Ce message confirme votre participation à « {{ $event->title }} ». Il ne constitue pas un reçu fiscal.</p>
        <p style="margin:0;">Si le bouton ne s'affiche pas : <a href="{{ $url }}" style="color:{{ $t->accentText }};word-break:break-all;">{{ $url }}</a></p>
    </x-slot:footer>
</x-mail.layout>
