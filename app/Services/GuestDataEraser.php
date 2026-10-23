<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Guest;
use Illuminate\Support\Str;

/**
 * Suppression des données personnelles des invités.
 *
 * Un invité n'est pas effacé de la base : sa ligne est anonymisée pour que les chiffres de l'événement
 * (réponses, couverts) et les montants des contributions restent exacts. Son lien d'invitation cesse de
 * fonctionner (nouveau jeton), ses messages du livre d'or et ses photos (fichiers compris) sont supprimés.
 */
class GuestDataEraser
{
    public const ERASED_NAME = 'Invité supprimé';

    public function erase(Guest $guest): void
    {
        $guest->guestbookEntries()->get()->each->delete();
        // Un par un : la suppression d'une photo retire aussi son fichier.
        $guest->photos()->get()->each->delete();
        $guest->contributions()->update(['message' => null]);

        $guest->forceFill([
            'name' => self::ERASED_NAME,
            'email' => null,
            'phone' => null,
            'token' => Str::random(40),
            'dietary_restrictions' => null,
            'favorite_song' => null,
            'rsvp_message' => null,
            'consent_at' => null,
            'erased_at' => now(),
            'erasure_requested_at' => null,
        ])->save();
    }

    /**
     * Efface les données de tous les invités de l'événement et révoque le lien traiteur.
     */
    public function purge(Event $event): int
    {
        $count = 0;

        $event->guests()->whereNull('erased_at')->get()->each(function (Guest $guest) use (&$count): void {
            $this->erase($guest);
            $count++;
        });

        $event->forceFill([
            'guest_data_purged_at' => now(),
            'caterer_token' => null,
            'caterer_link_expires_at' => null,
        ])->save();

        return $count;
    }
}
