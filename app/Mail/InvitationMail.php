<?php

namespace App\Mail;

use App\Models\Guest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Guest $guest) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Vous êtes invité(e) : {$this->guest->event->title}");
    }

    /**
     * Adresse affichée sous le nom du lieu : l'adresse géocodée commence souvent par ce nom, qu'on ne répète pas.
     */
    private function address(): ?string
    {
        $event = $this->guest->event;
        $address = trim((string) $event->location_address);
        $prefix = trim((string) $event->location_name).', ';

        if ($address !== '' && $prefix !== ', ' && str_starts_with($address, $prefix)) {
            $address = substr($address, strlen($prefix));
        }

        return $address === '' ? null : $address;
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.invitation',
            text: 'mail.invitation-text',
            with: [
                'guest' => $this->guest,
                'event' => $this->guest->event,
                'url' => $this->guest->invitationUrl(),
                'address' => $this->address(),
            ],
        );
    }
}
