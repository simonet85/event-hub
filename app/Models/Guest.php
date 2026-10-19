<?php

namespace App\Models;

use App\Enums\RsvpStatus;
use Database\Factories\GuestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'phone', 'max_companions'])]
class Guest extends Model
{
    /** @use HasFactory<GuestFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Guest $guest): void {
            $guest->token ??= Str::random(40);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'responded_at' => 'datetime',
            'consent_at' => 'datetime',
            'erased_at' => 'datetime',
            'rsvp_status' => RsvpStatus::class,
            'companions_count' => 'integer',
            'max_companions' => 'integer',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function guestbookEntries(): HasMany
    {
        return $this->hasMany(GuestbookEntry::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function invitationUrl(): string
    {
        return route('invitations.show', $this->token);
    }

    /**
     * Lien WhatsApp avec message pré-rempli, ou null sans numéro exploitable.
     */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        if ($digits === '' || $digits === null) {
            return null;
        }

        $message = "Bonjour {$this->name}, vous êtes invité(e) : {$this->event->title}. Votre invitation : {$this->invitationUrl()}";

        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }
}
