# Plan — Phase 1: Événements & Thèmes de base

## Group 1 — Initialisation du projet et dépendances
1. Créer le squelette Laravel : `laravel new event-hub --pest --git`.
2. Installer Breeze avec le profil Inertia Vue 3 + TypeScript : `php artisan breeze:install vue --typescript`.
3. Configurer Tailwind CSS avec les polices Google Fonts adaptées (Great Vibes, Playfair Display, Montserrat).

## Group 2 — Modèle de données & Migrations
4. Créer la migration `create_events_table` :
   - `id`, `user_id` (foreign key)
   - `title`, `slug` (unique index), `type` (enum : wedding, baptism, birthday, party, other)
   - `event_date`, `location_name`, `location_address`
   - `theme_config` (json : primary_color, secondary_color, font_family, cover_image_path)
   - `timestamps`
5. Créer le modèle `Event` avec casts Eloquent (`theme_config` => `array`, `event_date` => `datetime`).
6. Configurer la `EventPolicy` (`view`, `update`, `delete`).

## Group 3 — Contrôleurs & Logique Inertia
7. Créer `EventController` :
   - `index` : tableau de bord de l'hôte avec ses événements.
   - `create` / `store` : création d'un événement avec validation de formulaire (`StoreEventRequest`).
   - `showPublic` : vue publique optimisée pour les invités (`GET /e/{slug}`).
8. Configurer les routes dans `routes/web.php`.

## Group 4 — Composants Frontend Vue 3
9. Créer `resources/js/Layouts/EventPublicLayout.vue` :
   - Injection dynamique des CSS variables via `theme_config`.
   - Header avec compte à rebours (Countdown component).
   - Layout responsive sans distractions d'administration.
10. Créer `resources/js/Pages/Events/ShowPublic.vue` :
    - Carte d'invitation digitale hero.
    - Blocs d'informations clés (date, lieu, message d'accueil).
11. Créer `resources/js/Pages/Events/Create.vue` :
    - Sélecteur de type d'événement avec prévisualisation du thème en direct.

## Group 5 — Tests & Validation
12. Écrire les tests Pest pour `StoreEventRequest` et `EventPolicy`.
13. Écrire un test de composant Vitest pour le composant de compte à rebours `Countdown.vue`.
