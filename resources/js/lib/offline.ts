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

const STATIC_PATH = /^\/(build\/assets|fonts)\//;

export const offlineSupported = (): boolean => typeof navigator !== 'undefined' && 'serviceWorker' in navigator && typeof caches !== 'undefined';

/** Chemins des fichiers statiques déjà chargés par la page (de quoi réafficher le billet sans réseau). */
export function loadedStaticPaths(): string[] {
    const paths = performance
        .getEntriesByType('resource')
        .map((entry) => new URL(entry.name, location.href))
        .filter((url) => url.origin === location.origin && STATIC_PATH.test(url.pathname))
        .map((url) => url.pathname);

    return [...new Set(paths)];
}

export async function isOfflineTicketSaved(ticketPath: string): Promise<boolean> {
    if (!offlineSupported()) return false;

    return (await (await caches.open(CACHE_NAME)).match(ticketPath)) !== undefined;
}

/** Garde la page du billet et ses fichiers statiques. À appeler après un clic explicite de l'invité. */
export async function saveOfflineTicket(ticketPath: string): Promise<void> {
    if (!offlineSupported()) throw new Error('Le mode hors ligne n\'est pas disponible sur cet appareil.');

    await navigator.serviceWorker.register(SERVICE_WORKER_URL, { scope: SERVICE_WORKER_SCOPE });
    await navigator.serviceWorker.ready;

    const cache = await caches.open(CACHE_NAME);
    await cache.add(ticketPath);
    await Promise.all(loadedStaticPaths().map((path) => cache.add(path)));
}

/** Supprime le cache et désinscrit le service worker : à utiliser sur un appareil partagé. */
export async function clearOfflineTicket(): Promise<void> {
    if (typeof caches !== 'undefined') await caches.delete(CACHE_NAME);

    if ('serviceWorker' in navigator) {
        const registrations = await navigator.serviceWorker.getRegistrations();
        await Promise.all(registrations.filter((r) => r.active?.scriptURL.endsWith(SERVICE_WORKER_URL)).map((r) => r.unregister()));
    }
}
