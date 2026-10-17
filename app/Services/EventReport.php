<?php

namespace App\Services;

use App\Enums\ContributionStatus;
use App\Enums\ModerationStatus;
use App\Enums\RsvpStatus;
use App\Models\Event;
use App\Models\Guest;
use Illuminate\Support\Collection;

/**
 * Chiffres de l'événement : tableau de bord de l'hôte et exports (Excel, CSV, PDF traiteur, plan de table).
 */
class EventReport
{
    public const NO_TABLE = 'Sans table';

    public function __construct(private readonly Event $event) {}

    /**
     * @return Collection<int, Guest>
     */
    public function guests(): Collection
    {
        return $this->event->guests()->orderBy('name')->orderBy('id')->get();
    }

    /**
     * @return array{total: int, to_invite: int, pending: int, accepted: int, declined: int, headcount: int, response_rate: int}
     */
    public function rsvp(): array
    {
        $guests = $this->guests();
        $accepted = $guests->where('rsvp_status', RsvpStatus::Accepted);
        $answered = $guests->count() - $guests->where('rsvp_status', RsvpStatus::Pending)->count();

        return [
            'total' => $guests->count(),
            'to_invite' => $guests->filter(fn (Guest $g) => $g->email && ! $g->invited_at)->count(),
            'pending' => $guests->where('rsvp_status', RsvpStatus::Pending)->count(),
            'accepted' => $accepted->count(),
            'declined' => $guests->where('rsvp_status', RsvpStatus::Declined)->count(),
            'headcount' => $accepted->count() + $accepted->sum('companions_count'),
            'response_rate' => $guests->isEmpty() ? 0 : (int) round($answered / $guests->count() * 100),
        ];
    }

    /**
     * Régimes alimentaires des invités présents, avec le nombre de couverts concernés (invité + accompagnants).
     *
     * @return Collection<int, array{guest: string, table: string|null, restriction: string, seats: int}>
     */
    public function dietary(): Collection
    {
        return $this->guests()
            ->where('rsvp_status', RsvpStatus::Accepted)
            ->filter(fn (Guest $g) => filled($g->dietary_restrictions))
            ->map(fn (Guest $g) => [
                'guest' => $g->name,
                'table' => $g->table_name,
                'restriction' => trim($g->dietary_restrictions),
                'seats' => 1 + $g->companions_count,
            ])
            ->values();
    }

    /**
     * Plan de table : invités présents (et en attente de réponse) regroupés par table ; les absents sont exclus.
     *
     * @return Collection<int, array{table: string, guests: Collection<int, Guest>, headcount: int}>
     */
    public function seating(): Collection
    {
        return $this->guests()
            ->where('rsvp_status', '!=', RsvpStatus::Declined)
            ->groupBy(fn (Guest $g) => $g->table_name ?: self::NO_TABLE)
            ->map(fn (Collection $guests, string $table) => [
                'table' => $table,
                'guests' => $guests->values(),
                'headcount' => $guests->sum(fn (Guest $g) => $g->rsvp_status === RsvpStatus::Accepted ? 1 + $g->companions_count : 1),
            ])
            ->sortBy(fn (array $row) => [$row['table'] === self::NO_TABLE ? 1 : 0, mb_strtolower($row['table'])])
            ->values();
    }

    /**
     * @return array{raised: int, goal: int|null, pending: int, to_review: int}
     */
    public function cagnotte(): array
    {
        $contributions = $this->event->contributions();

        return [
            'raised' => (int) (clone $contributions)->where('status', ContributionStatus::Confirmed)->sum('amount'),
            'goal' => $this->event->cagnotte_goal,
            'pending' => (clone $contributions)->where('status', ContributionStatus::Pending)->count(),
            'to_review' => (clone $contributions)->where('status', ContributionStatus::Mismatch)->count(),
        ];
    }

    /**
     * @return array{entries: int, photos: int}
     */
    public function moderationQueue(): array
    {
        return [
            'entries' => $this->event->guestbookEntries()->where('status', ModerationStatus::Pending)->count(),
            'photos' => $this->event->photos()->where('status', ModerationStatus::Pending)->count(),
        ];
    }

    /**
     * Ligne d'export d'un invité. Les valeurs saisies par les invités sont neutralisées contre l'injection de formules.
     *
     * @return array<int, string|int>
     */
    public function guestRow(Guest $guest): array
    {
        $accepted = $guest->rsvp_status === RsvpStatus::Accepted;

        return array_map(self::safeCell(...), [
            $guest->name,
            (string) $guest->email,
            (string) $guest->phone,
            $guest->rsvp_status->label(),
            $accepted ? $guest->companions_count : '',
            $accepted ? 1 + $guest->companions_count : '',
            (string) $guest->table_name,
            (string) $guest->dietary_restrictions,
            (string) $guest->favorite_song,
            (string) $guest->rsvp_message,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function guestHeaders(): array
    {
        return ['Nom', 'Email', 'Téléphone', 'Réponse', 'Accompagnants', 'Couverts', 'Table', 'Régime alimentaire', 'Chanson', 'Message'];
    }

    /**
     * Neutralise les cellules interprétables comme formule par Excel / LibreOffice (=, +, -, @, tabulation, retour chariot).
     * Un numéro de téléphone international (« +33 6 12 34 56 78 ») est conservé tel quel : il ne contient que des chiffres et des espaces.
     */
    public static function safeCell(string|int $value): string|int
    {
        if (is_int($value)) {
            return $value;
        }

        if (preg_match('/^\+\d[\d ]*$/', $value) === 1) {
            return $value;
        }

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
