# Roadmap design

Suite de `specs/roadmap.md`, côté interface. Direction retenue : **« Papier & lumière »** (jetons de `ClaudeDesign/DESIGN-SYSTEM.md`, correspondance dans `ClaudeDesign/CORRESPONDANCE-JETONS.md`) : surfaces crème, Cormorant Garamond / DM Sans / Pinyon Script, angles de 2 px, un accent par événement. L'ancienne direction « Éditorial chic » reste dans l'historique git (commit `221c7d8`).

## Réalisé

- [x] Design system : jetons CSS (`--ink`, `--paper`, `--accent`, `--gold`), classes `.display`, `.eyebrow`, `.chip`, `.card`, `.btn`, `.input`, `.check`
- [x] Composants `Components/ui/` : `Reveal`, `Marquee`, `ScrollExpandHero`, `LocaleSwitcher`, `FormField`, `EventHeader`, `EventHero`, `StatCard`
- [x] Landing : bandeau défilant, hero « scroll expansion », sélecteur de thèmes en direct
- [x] Invitation et page publique : accroche plein écran, billet QR, sections qui apparaissent au scroll, compte à rebours animé
- [x] Espace hôte : cartes d'événements, onglets, cartes de statistiques, formulaires refaits
- [x] Thème dynamique par événement (couleurs et police injectées en variables CSS)
- [x] `prefers-reduced-motion` respecté (reveal, marquee, expansion)

- [x] Papier & lumière, premier passage : jetons clair/sombre, thèmes `ev-*`, composants InvitationCard, StatusPill, GiftProgress, barre de navigation invité, RSVP segmenté

## Améliorations à venir

### Reprises de la vidéo de référence (non faites)

- [ ] **Hero vidéo** : vidéo en boucle révélée derrière l'image qui s'ouvre au scroll, ou vidéo synchronisée au scroll (`currentTime = progression × durée`, vidéo très compressée, nombreuses images clés). Dossier `input/` pour déposer les médias de l'hôte.
- [ ] **Visuel 3D interactif** (modèle GLB via `<model-viewer>`) pour un élément signature d'un événement (gâteau, alliances, faire-part), chargé à la demande.
- [x] **Panneau « Tweaks »** pour l'hôte, version minimale (formulaire d'événement, aperçu en direct) : préréglage par type, couleur d'accent, fond et police, écrits dans `theme_config` avec l'avertissement de contraste. Rayon des coins et densité : non faits, volontairement.
- [ ] **Témoignages animés** : défilement des messages du livre d'or approuvés sur la page publique.
- [ ] **Bibliothèque d'inspiration** : trois références visuelles minimum par nouvelle direction, avant de lancer une refonte.

### Qualité et accessibilité

- [ ] **Vérification mobile réelle** à 320, 375, 390 et 768 px (95 % des invités sont sur smartphone), avec captures de référence.
- [x] **Audit de contraste AA** : préréglages conformes (testés), avertissement de l'hôte quand une couleur choisie est illisible, anciens préréglages migrés.
- [ ] **Mode sombre** de l'espace hôte (`prefers-color-scheme`) : jetons sombres prêts (`[data-theme="dark"]`, utilisé par le thème Fête) ; l'espace hôte reste clair tant que ses composants ne sont pas tous passés aux jetons.
- [ ] **Navigation au clavier** : parcours complet de l'invitation et des formulaires, focus visibles, fenêtres modales piégeant le focus.
- [x] **Polices auto-hébergées** (Cormorant Garamond, DM Sans, Pinyon Script, woff2, `font-display: swap`, préchargement).

### Expérience invité

- [x] **Ouverture d'enveloppe** : animation d'entrée sur l'invitation (2,6 s), jouée une fois par invité et appareil, refermée au moindre geste, jamais bloquante, ignorée avec `prefers-reduced-motion` ou un lien direct vers une ancre.
- [x] **Confettis** à la confirmation de présence et au don confirmé (canvas sans dépendance, `prefers-reduced-motion` respecté).
- [ ] **Galerie** : visionneuse plein écran (lightbox) avec navigation clavier et gestes tactiles.
- [ ] **Image de partage** (Open Graph) générée par événement, pour les aperçus WhatsApp et messageries.
- [ ] **Invitation hors ligne** (PWA) : billet QR consultable sans réseau le jour J.
- [ ] **Transitions de pages** Inertia et squelettes de chargement.

### Espace hôte

- [ ] **Plan de table visuel** par glisser-déposer.
- [ ] **Galerie de thèmes prédéfinis** avec aperçu, et bouton « Réinitialiser le thème » selon le type d'événement.
- [ ] **États vides illustrés** (invités, programme, cagnotte, modération).
- [ ] **Gabarits d'emails** d'invitation et de relance aux couleurs du thème.
- [ ] **Styles d'impression** cohérents pour les PDF (traiteur, plan de table).

### Traduction

- [ ] **Pages hôte et invité en anglais** : passer les textes par `__()` et générer `lang/en.json` avec `php artisan deepl:translate en`.
- [ ] **Autres langues** (es, de) : ajouter à `SetLocale::SUPPORTED` et au sélecteur.
