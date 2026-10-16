<?php

namespace App\Models;

use App\Enums\ModerationStatus;
use Database\Factories\PhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['event_id', 'path', 'caption', 'status'])]
class Photo extends Model
{
    /** @use HasFactory<PhotoFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::deleting(function (Photo $photo): void {
            Storage::disk('local')->delete($photo->path);
        });
    }

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

    public function url(): string
    {
        return route('photos.show', $this);
    }
}
