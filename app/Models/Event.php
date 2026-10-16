<?php

namespace App\Models;

use App\Enums\EventType;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'type', 'event_date', 'location_name', 'location_address', 'welcome_message', 'theme_config', 'dress_code', 'latitude', 'longitude'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'event_date' => 'datetime',
            'theme_config' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function scheduleItems(): HasMany
    {
        return $this->hasMany(ScheduleItem::class)->orderBy('starts_at')->orderBy('id');
    }

    /**
     * Données communes aux pages vues par les invités (page publique et invitation).
     *
     * @return array<string, mixed>
     */
    public function publicPayload(): array
    {
        $hasCoordinates = $this->latitude !== null && $this->longitude !== null;
        $query = $hasCoordinates
            ? "{$this->latitude},{$this->longitude}"
            : trim("{$this->location_name} {$this->location_address}");

        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'event_date' => $this->event_date->toIso8601String(),
            'location_name' => $this->location_name,
            'location_address' => $this->location_address,
            'welcome_message' => $this->welcome_message,
            'theme_config' => $this->theme_config,
            'dress_code' => $this->dress_code,
            'map' => $hasCoordinates ? [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'embed_url' => 'https://www.openstreetmap.org/export/embed.html?bbox='
                    .implode(',', [$this->longitude - 0.005, $this->latitude - 0.003, $this->longitude + 0.005, $this->latitude + 0.003])
                    ."&layer=mapnik&marker={$this->latitude},{$this->longitude}",
            ] : null,
            'map_url' => $query === '' ? null : 'https://www.openstreetmap.org/search?query='.rawurlencode($query),
            'schedule' => $this->scheduleItems->map(fn (ScheduleItem $item) => [
                'id' => $item->id,
                'starts_at' => $item->starts_at->toIso8601String(),
                'title' => $item->title,
                'description' => $item->description,
            ])->all(),
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
