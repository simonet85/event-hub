<?php

namespace App\Models;

use App\Enums\ContributionStatus;
use Database\Factories\GiftFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'target_amount'])]
class Gift extends Model
{
    /** @use HasFactory<GiftFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['target_amount' => 'integer'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    /**
     * Montant déjà financé : uniquement les paiements confirmés par MoneyFusion.
     */
    public function raised(): int
    {
        return (int) $this->contributions()->where('status', ContributionStatus::Confirmed)->sum('amount');
    }
}
