<?php

namespace App\Jobs;

use App\Mail\InvitationMail;
use App\Models\Guest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInvitation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [30, 120];

    public function __construct(public Guest $guest, public bool $resend = false) {}

    /**
     * Envoie le mail puis marque l'invité comme invité : si le transport échoue,
     * l'exception remet le job en file et `invited_at` reste vide.
     */
    public function handle(): void
    {
        $guest = $this->guest->fresh();

        if (! $guest || ! $guest->email || (! $this->resend && $guest->invited_at)) {
            return;
        }

        Mail::to($guest->email)->send(new InvitationMail($guest));

        $guest->forceFill(['invited_at' => now()])->save();
    }
}
