# Tech Stack

Architecture monolithique moderne hybride : Laravel comme cœur métier robuste couplé à Vue.js 
via Inertia.js pour une expérience Single-Page Application (SPA) fluide, sans complexité d'API REST découplée.

## Stack Principale

| Couche | Technologie | Rationale |
|---|---|---|
| **Backend & ORM** | **Laravel 11+ (PHP 8.3+)** | Eloquent ORM, queues asynchrones, notifications, système de Policies & Auth robuste |
| **Bridge Fullstack** | **Inertia.js v2** | Évite l'échafaudage d'API REST ; routing et contrôleurs côté Laravel, vues côté Vue 3 |
| **Frontend** | **Vue.js 3 (Composition API / <script setup>)** | Réactivité native, modularité des composants, performances élevées |
| **Typage UI** | **TypeScript** | Typage fort des props et formulaires frontend |
| **Styling** | **Tailwind CSS + Headless UI** | Design mobile-first sur-mesure, thèmes personnalisables par événement |
| **Base de données** | **PostgreSQL** (ou **MySQL 8**) | Support natif des données relationnelles et champs JSON pour les thèmes |
| **Média & Fichiers**| **Laravel MediaLibrary (Spatie)** | Redimensionnement optimisé des photos d'invités et bannières |
| **Tests Backend** | **Pest PHP** | Framework de test concis, lisible et expressif |
| **Tests Frontend** | **Vitest + Playwright** | Validation des composants Vue et tests E2E du parcours RSVP |

## Ce que nous n'utilisons PAS
- Pas d'architecture REST / SPA totalement séparée (évite la duplication des règles de validation et tokens JWT).
- Pas de bibliothèques CSS encombrantes à thèmes rigides (Bootstrap).
