# Phase 2 — Invités & Invitations

## Périmètre
- Table `guests` (nom, email, téléphone, accompagnants max, `token` unique de 40 caractères, `invited_at`).
- Gestion par l'hôte : ajout, suppression, import CSV (`name,email,phone,max_companions`), protégé par `EventPolicy`.
- Invitation personnalisée publique `GET /i/{token}` avec QR code (généré côté client, paquet `qrcode`).
- Envoi par email (`InvitationMail`, Mailpit en dev) : en masse (invitations en attente) ou individuel.
- Lien WhatsApp `wa.me` pré-rempli.

## Décisions
- Le jeton est la seule preuve d'accès à l'invitation (pas de compte invité).
- Email unique par événement ; doublons et lignes invalides ignorés à l'import.
- Les tests n'exigent pas de build Vite (`withoutVite()` dans `TestCase`).

## Hors périmètre
- Formulaire RSVP et suivi des réponses (Phase 3).
- Relances automatiques.

## Validation
`php artisan test`, `npm test`, `npm run typecheck`, `npm run build` sans erreur.