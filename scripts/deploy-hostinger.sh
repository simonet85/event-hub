#!/usr/bin/env bash
#
# Déploiement d'Event Hub sur un hébergement mutualisé (Hostinger) par SSH, sans rsync ni Git sur le serveur.
#
# Variables (aucune valeur secrète dans ce fichier : l'accès se fait par clé SSH, jamais par mot de passe) :
#   DEPLOY_SSH   options et destination SSH, ex. "-p 65002 utilisateur@203.0.113.10"   (obligatoire)
#   DEPLOY_DIR   dossier de l'application sur le serveur, ex. "domains/mon-site.com/eventhub" (obligatoire, relatif au HOME)
#   DEPLOY_PHP   binaire PHP du serveur (défaut : /opt/alt/php84/usr/bin/php)
#   DEPLOY_MIGRATE=1  applique aussi les migrations (à activer une fois le .env de production prêt)
#
# Première installation (une seule fois, à la main) : créer DEPLOY_DIR/.env à partir de .env.example,
# lier le dossier web vers DEPLOY_DIR/public, puis php artisan key:generate.
set -euo pipefail

: "${DEPLOY_SSH:?DEPLOY_SSH manquant (ex. \"-p 65002 utilisateur@hote\")}"
: "${DEPLOY_DIR:?DEPLOY_DIR manquant (ex. \"domains/mon-site.com/eventhub\")}"
PHP="${DEPLOY_PHP:-/opt/alt/php84/usr/bin/php}"

cd "$(dirname "$0")/.."

if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "Des modifications ne sont pas commitées : le déploiement part du dernier commit." >&2
    exit 1
fi

echo "→ Compilation des assets"
npm run build >/dev/null

# shellcheck disable=SC2086
SSH="ssh $DEPLOY_SSH"

echo "→ Envoi du code ($(git rev-parse --short HEAD))"
$SSH "mkdir -p $DEPLOY_DIR"
git archive HEAD | $SSH "tar -x -C $DEPLOY_DIR"

echo "→ Envoi des assets compilés"
tar -c -C public build | $SSH "tar -x -C $DEPLOY_DIR/public"

echo "→ Installation des dépendances et mise en cache"
$SSH "set -e; cd $DEPLOY_DIR
    $PHP /usr/local/bin/composer install --no-dev --optimize-autoloader --no-interaction --no-progress
    mkdir -p storage/app/private storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache"

if [ "${DEPLOY_MIGRATE:-0}" = "1" ]; then
    echo "→ Migrations"
    $SSH "cd $DEPLOY_DIR && $PHP artisan migrate --force"
fi

$SSH "cd $DEPLOY_DIR
    $PHP artisan optimize:clear >/dev/null
    $PHP artisan config:cache && $PHP artisan route:cache && $PHP artisan view:cache && $PHP artisan event:cache
    $PHP artisan queue:restart >/dev/null 2>&1 || true"

echo "✓ Déployé"
