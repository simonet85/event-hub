/*
 * Service worker du billet hors ligne.
 *
 * Périmètre volontairement minimal : il ne conserve que la page du billet de l'invité lui-même
 * (/i/{jeton}/billet) et les fichiers statiques de l'interface (/build/assets, /fonts). Rien d'autre
 * n'est intercepté : ni livre d'or, ni photos, ni liste d'invités, ni paiements. Il ne met rien en
 * cache de lui-même : seul un clic explicite sur « Garder mon billet hors ligne » remplit le cache,
 * et le bouton « Effacer » le vide (voir resources/js/lib/offline.ts).
 */
const CACHE = 'eventhub-billet-v1';
const TICKET_PATH = /^\/i\/[^/]+\/billet$/;
const STATIC_PATH = /^\/(build\/assets|fonts)\//;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    if (request.mode === 'navigate' && TICKET_PATH.test(url.pathname)) {
        event.respondWith(ticket(request, url.pathname));
    } else if (STATIC_PATH.test(url.pathname)) {
        event.respondWith(staticFile(request, url.pathname));
    }
});

/**
 * Billet : réseau d'abord ; hors ligne, la copie gardée par l'invité (s'il en a gardé une).
 *
 * Limite connue : hors ligne, le service worker ne peut pas savoir que l'événement a été archivé depuis.
 * Dès que l'appareil retrouve le réseau et que le serveur répond 404 ou 410 (événement archivé, invité retiré),
 * la copie est supprimée : « archivé » redevient « introuvable » à la première visite en ligne.
 */
async function ticket(request, path) {
    const cache = await caches.open(CACHE);
    const stored = await cache.match(path);

    try {
        const response = await fetch(request);

        if (response.status === 404 || response.status === 410) {
            await cache.delete(path);
        } else if (stored && response.ok) {
            // On rafraîchit la copie seulement si l'invité a choisi de la garder.
            await cache.put(path, response.clone());
        }

        return response;
    } catch (error) {
        if (stored) return stored;
        throw error;
    }
}

/** Fichiers statiques : la copie gardée si elle existe, sinon le réseau tel quel. */
async function staticFile(request, path) {
    const cache = await caches.open(CACHE);
    const stored = await cache.match(path);

    return stored || fetch(request);
}
