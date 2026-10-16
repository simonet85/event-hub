# Validation — Phase 1

## 1. Tests Automatisés Backend (Pest)
```bash
php artisan test
```
Vérifications requises :
- Un utilisateur authentifié peut créer un événement avec un slug unique.
- La création échoue si le type d'événement est invalide ou la date passée.
- Un utilisateur non propriétaire ne peut pas modifier l'événement (403 Forbidden).
- La route publique `GET /e/{slug}` renvoie HTTP 200 sans nécessiter d'authentification.

## 2. Typage et Linting Frontend
```bash
npm run typecheck # vue-tsc --noEmit
npm run lint
```
Doit retourner code 0 sans erreurs TypeScript.

## 3. Checklist Smoke Test Manuel
- [ ] Se connecter en tant qu'hôte et créer un événement de type "Mariage".
- [ ] Changer la couleur principale dans l'interface et observer le changement immédiat.
- [ ] Ouvrir l'URL publique `/e/<slug>` dans un navigateur privé (mode invité).
- [ ] Vérifier sur simulateur mobile (375px) :
  - Pas de débordement horizontal.
  - La police festive et le compte à rebours s'affichent nettement.
