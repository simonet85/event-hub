/**
 * Billet hors ligne : seule la page du billet de l'invité et les fichiers statiques de l'interface sont gardés,
 * jamais les données d'autres personnes. L'invité garde et efface cette copie lui-même.
 */
export const CACHE_NAME = 'eventhub-billet-v1';
export const SERVICE_WORKER_URL = '/billet-sw.js';
/**
 * Portée du service worker : seules les pages sous /i/ (invitation et billet) sont contrôlées. Leurs fichiers
 * statiques passent aussi par lui (une page contrôlée y fait transiter toutes ses requêtes), le reste du site non.
 */
export const SERVICE_WORKER_SCOPE = '/i/';

const TICKET_PATH = /^\/i\/[^/]+\/billet$/;

export const offlineSupported = (): boolean => typeof navigator !== 'undefined' && 'serviceWorker' in navigator && typeof caches !== 'undefined';

export async function isOfflineTicketSaved(ticketPath: string): Promise<boolean> {
    if (!offlineSupported()) return false;

    return (await (await caches.open(CACHE_NAME)).match(ticketPath)) !== undefined;
}

/**
 * Garde la page du billet et exactement les fichiers statiques indiqués par le serveur (`assets`) : jamais ce que la
 * page a pu précharger pour d'autres pages. À appeler après un clic explicite de l'invité.
 */
export async function saveOfflineTicket(ticketPath: string, assets: string[]): Promise<void> {
    if (!offlineSupported()) throw new Error('Le mode hors ligne n\'est pas disponible sur cet appareil.');

    await navigator.serviceWorker.register(SERVICE_WORKER_URL, { scope: SERVICE_WORKER_SCOPE });
    await navigator.serviceWorker.ready;

    const cache = await caches.open(CACHE_NAME);
    const wanted = new Set(assets);

    // Une copie plus ancienne a pu garder des fichiers devenus inutiles : on ne garde que le billet (le sien et
    // ceux d'autres invités du même appareil) et les fichiers demandés.
    for (const request of await cache.keys()) {
        const path = new URL(request.url).pathname;
        if (!wanted.has(path) && !TICKET_PATH.test(path)) await cache.delete(request);
    }

    await Promise.all([ticketPath, ...wanted].map((path) => cache.add(path)));
}

/** Supprime le cache et désinscrit le service worker : à utiliser sur un appareil partagé. */
export async function clearOfflineTicket(): Promise<void> {
    if (typeof caches !== 'undefined') await caches.delete(CACHE_NAME);

    if ('serviceWorker' in navigator) {
        const registrations = await navigator.serviceWorker.getRegistrations();
        await Promise.all(registrations.filter((r) => r.active?.scriptURL.endsWith(SERVICE_WORKER_URL)).map((r) => r.unregister()));
    }
}
