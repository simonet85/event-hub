<?php

namespace App\Models;

use App\Enums\ContributionStatus;
use App\Enums\EventType;
use App\Services\ShareImage;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'slug', 'type', 'event_date', 'location_name', 'location_address', 'welcome_message', 'theme_config', 'dress_code', 'latitude', 'longitude', 'cagnotte_enabled', 'cagnotte_goal', 'cagnotte_message'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Le lien traiteur est actif tant qu'il existe et n'a pas expiré.
     */
    public function hasActiveCatererLink(): bool
    {
        return $this->caterer_token !== null && $this->caterer_link_expires_at?->isFuture() === true;
    }

    protected function casts(): array
    {
        return [
            'type' => EventType::class,
            'event_date' => 'datetime',
            'archived_at' => 'datetime',
            'caterer_link_expires_at' => 'datetime',
            'guest_data_purged_at' => 'datetime',
            'theme_config' => 'array',
            'cagnotte_enabled' => 'boolean',
            'cagnotte_goal' => 'integer',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    /**
     * Balises d'aperçu de lien (Open Graph) : uniquement des informations de l'événement,
     * identiques pour tous les invités, et jamais de donnée personnelle.
     *
     * @return array{title: string, description: string, url: string, image: string|null, noindex: true}
     */
    public function shareMeta(): array
    {
        $when = app(ShareImage::class)->dateLine($this);

        return [
            'title' => $this->title,
            'description' => trim("{$this->type->label()} · {$when}".($this->location_name ? " · {$this->location_name}" : '')),
            // Adresse canonique de l'événement (jamais le lien personnel d'un invité, qui contient son jeton).
            'url' => route('events.public', $this),
            'image' => app(ShareImage::class)->available()
                ? route('events.share-image', $this).'?v='.app(ShareImage::class)->fingerprint($this)
                : null,
            'noindex' => true,
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /** @param  Builder<Event>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /** @param  Builder<Event>  $query */
    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function gifts(): HasMany
    {
        return $this->hasMany(Gift::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    /**
     * Cagnotte et liste de cadeaux vues par un invité. Seules les contributions
     * confirmées par MoneyFusion comptent ; les montants individuels des autres invités restent privés.
     *
     * @return array<string, mixed>|null
     */
    public function cagnottePayload(Guest $guest): ?array
    {
        if (! $this->cagnotte_enabled) {
            return null;
        }

        $confirmed = $this->contributions()->where('status', ContributionStatus::Confirmed);
        $raisedByGift = (clone $confirmed)->whereNotNull('gift_id')->selectRaw('gift_id, sum(amount) as raised')->groupBy('gift_id')->pluck('raised', 'gift_id');

        return [
            'goal' => $this->cagnotte_goal,
            'message' => $this->cagnotte_message,
            'raised' => (int) (clone $confirmed)->sum('amount'),
            'gifts' => $this->gifts()->orderBy('id')->get()->map(function (Gift $gift) use ($raisedByGift): array {
                $raised = (int) ($raisedByGift[$gift->id] ?? 0);

                return [
                    'id' => $gift->id,
                    'name' => $gift->name,
                    'description' => $gift->description,
                    'target_amount' => $gift->target_amount,
                    'raised' => $raised,
                    'remaining' => max(0, $gift->target_amount - $raised),
                ];
            })->all(),
            'mine' => $guest->contributions()->with('gift:id,name')->latest()->limit(20)->get()->map(fn (Contribution $c): array => [
                'id' => $c->id,
                'amount' => $c->amount,
                'gift' => $c->gift?->name,
                'status' => $c->status->value,
                'status_label' => $c->status->label(),
                'payment_url' => $c->status === ContributionStatus::Pending ? $c->payment_url : null,
            ])->all(),
        ];
    }

    public function guestbookEntries(): HasMany
    {
        return $this->hasMany(GuestbookEntry::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
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
            'theme_preset' => $this->type->defaultTheme(),
            'css_class' => $this->type->cssClass(),
            'dark_theme' => $this->type->prefersDarkTheme(),
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
