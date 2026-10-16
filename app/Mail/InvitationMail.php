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

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.invitation',
            with: [
                'guest' => $this->guest,
                'event' => $this->guest->event,
                'url' => $this->guest->invitationUrl(),
            ],
        );
    }
}
