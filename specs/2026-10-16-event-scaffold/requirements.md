# Requirements — Phase 1: Événements & Thèmes de base

## Scope
1. Mise en place du socle Laravel 11 + Breeze (Inertia Vue 3 + TypeScript + Tailwind CSS).
2. Entités : `User` (Hôte) et `Event` (types : `wedding`, `baptism`, `birthday`, `other`).
3. Système de personnalisation visuelle : palette de couleurs, police, bannière d'en-tête.
4. Route publique visualisable par URL slug : `GET /e/{slug}`.

## Decisions
- **Slug unique sécurisé** : Utilisation d'un slug aléatoire ou personnalisable (ex: `mariage-clara-thomas-2026`).
- **Thème dynamique** : Les attributs visuels sont stockés sous forme de colonne JSON `theme_config` dans la table `events`, injectée sous forme de variables CSS `:root` dans le layout Vue.
- **Permissions** : Seul l'hôte propriétaire peut modifier son événement via une Laravel Policy.

## Out of Scope
- Gestion des invités et envoi de mails (Phase 2).
- Formulaire RSVP (Phase 3).
- Paiements et cagnottes (Phase 6).
