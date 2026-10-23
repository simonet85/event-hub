<?php

namespace App\Mail;

use App\Models\Contribution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reçu envoyé à l'invité quand sa contribution est confirmée (paiement vérifié auprès de MoneyFusion).
 */
class ContributionReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Contribution $contribution) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Merci pour votre contribution : {$this->contribution->event->title}");
    }

    public function content(): Content
    {
        $guest = $this->contribution->guest;

        return new Content(
            view: 'mail.contribution-receipt',
            text: 'mail.contribution-receipt-text',
            with: [
                'contribution' => $this->contribution,
                'guest' => $guest,
                'event' => $this->contribution->event,
                'amount' => number_format($this->contribution->amount, 0, ',', "\u{00A0}")."\u{00A0}F CFA",
                'target' => $this->contribution->gift?->name ?? 'La cagnotte générale',
                'paidAt' => ($this->contribution->confirmed_at ?? now())->locale('fr')->isoFormat('D MMMM YYYY [à] HH:mm'),
                'url' => $guest->invitationUrl().'?onglet=cadeaux',
            ],
        );
    }
}
