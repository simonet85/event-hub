<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Database\Factories\GuestbookEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'message', 'status'])]
class GuestbookEntry extends Model
{
    /** @use HasFactory<GuestbookEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['status' => ModerationStatus::class];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
