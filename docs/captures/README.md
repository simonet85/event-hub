# Captures de référence (mobile)

Page d'invitation (thème Mariage), mesurée à 320, 375, 390 et 768 px de large : aucun défilement horizontal, cibles tactiles d'au moins 44 px, barre de navigation du bas défilante. À comparer après toute modification du design.

| Largeur | Fichier |
|---|---|
| 320 px | `invitation-320.png` |
| 375 px | `invitation-375.png` |
| 390 px | `invitation-390.png` |
| 768 px | `invitation-768.png` |

Méthode : la page est chargée dans un `<iframe>` de la largeur voulue (les requêtes média répondent à la largeur de l'iframe), puis `document.documentElement.scrollWidth - largeur` doit rester inférieur ou égal à 0.
