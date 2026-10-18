<?php

namespace Database\Seeders;

use App\Enums\EventType;
use App\Models\Event;
use App\Models\Gift;
use App\Models\Guest;
use App\Models\ScheduleItem;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration pour tester l'application en local (jamais en production).
 * Compte de test : demo@example.test / mot de passe ci-dessous (valeurs de développement uniquement).
 */
class DemoSeeder extends Seeder
{
    public const EMAIL = 'demo@example.test';

    public const PASSWORD = 'demo-local-Pa55word';

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => self::EMAIL],
            ['name' => 'Hôte Démo', 'password' => self::PASSWORD, 'email_verified_at' => now()],
        );

        $event = Event::updateOrCreate(['slug' => 'mariage-demo'], [
            'user_id' => $user->id,
            'title' => 'Mariage Clara & Thomas',
            'type' => 'wedding',
            'event_date' => now()->addDays(60)->setTime(15, 0),
            'location_name' => 'Château de Versailles',
            'location_address' => 'Place d\'Armes, 78000 Versailles',
            'welcome_message' => 'Nous sommes heureux de vous compter parmi nous !',
            'theme_config' => EventType::Wedding->defaultTheme(),
            'dress_code' => 'Tenue de soirée',
            'latitude' => 48.8049,
            'longitude' => 2.1204,
            'cagnotte_enabled' => true,
            'cagnotte_goal' => 500000,
            'cagnotte_message' => 'Votre présence est notre plus beau cadeau.',
        ]);

        if (! $event->scheduleItems()->exists()) {
            foreach ([['15:00', 'Cérémonie'], ['17:00', 'Cocktail'], ['20:00', 'Dîner']] as [$time, $title]) {
                ScheduleItem::create(['event_id' => $event->id, 'title' => $title, 'starts_at' => $event->event_date->copy()->setTimeFromTimeString($time)]);
            }
        }

        if (! $event->gifts()->exists()) {
            Gift::create(['event_id' => $event->id, 'name' => 'Voyage de noces', 'description' => 'Une semaine à Zanzibar', 'target_amount' => 300000]);
        }

        foreach ([
            ['Alice Martin', 'alice@example.test', '+33 6 12 34 56 78', 2],
            ['Bob Durand', 'bob@example.test', null, 0],
            ['Chloé Bernard', null, '+225 07 07 07 07 07', 1],
        ] as [$name, $email, $phone, $companions]) {
            Guest::firstOrCreate(['event_id' => $event->id, 'name' => $name], ['email' => $email, 'phone' => $phone, 'max_companions' => $companions]);
        }
    }
}
