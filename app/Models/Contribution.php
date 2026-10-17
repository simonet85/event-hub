<?php

namespace App\Models;

use App\Enums\ContributionStatus;
use Database\Factories\ContributionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'gift_id', 'amount', 'message', 'status', 'payment_token', 'payment_url', 'confirmed_at'])]
class Contribution extends Model
{
    /** @use HasFactory<ContributionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => ContributionStatus::class,
            'confirmed_at' => 'datetime',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    public function gift(): BelongsTo
    {
        return $this->belongsTo(Gift::class);
    }
}
