import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { CACHE_NAME, clearOfflineTicket, isOfflineTicketSaved, offlineSupported, saveOfflineTicket, SERVICE_WORKER_SCOPE, SERVICE_WORKER_URL } from './offline';

const TICKET = '/i/jeton/billet';
const ASSETS = ['/build/assets/app-abc.js', '/build/assets/Ticket-def.js', '/fonts/dm-sans-normal-latin.woff2'];

function fakeCaches() {
    const store = new Map<string, unknown>();
    const cache = {
        add: vi.fn(async (path: string) => void store.set(path, 'réponse')),
        match: vi.fn(async (path: string) => store.get(path)),
        keys: vi.fn(async () => [...store.keys()].map((path) => ({ url: `http://localhost${path}` }))),
        delete: vi.fn(async (request: { url: string }) => store.delete(new URL(request.url).pathname)),
    };

    return {
        store,
        cache,
        api: { open: vi.fn(async () => cache), delete: vi.fn(async () => true) },
    };
}

let fake: ReturnType<typeof fakeCaches>;
let register: ReturnType<typeof vi.fn>;
let unregister: ReturnType<typeof vi.fn>;

beforeEach(() => {
    fake = fakeCaches();
    register = vi.fn(async () => ({}));
    unregister = vi.fn(async () => true);
    vi.stubGlobal('caches', fake.api);
    Object.defineProperty(navigator, 'serviceWorker', {
        configurable: true,
        value: {
            register,
            ready: Promise.resolve({}),
            getRegistrations: vi.fn(async () => [
                { active: { scriptURL: `http://localhost${SERVICE_WORKER_URL}` }, unregister },
                { active: { scriptURL: 'http://localhost/autre-sw.js' }, unregister: vi.fn() },
            ]),
        },
    });
});

afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
});

describe('billet hors ligne', () => {
    it('garde la page du billet et exactement les fichiers indiqués par le serveur', async () => {
        // Ce que la page a pu précharger pour d'autres pages ne compte pas : seule la liste du serveur fait foi.
        vi.spyOn(performance, 'getEntriesByType').mockReturnValue([{ name: `${location.origin}/build/assets/Login-xyz.js` }] as unknown as PerformanceEntryList);

        await saveOfflineTicket(TICKET, ASSETS);

        // Portée étroite : seulement /i/, pas tout le site.
        expect(register).toHaveBeenCalledWith(SERVICE_WORKER_URL, { scope: SERVICE_WORKER_SCOPE });
        expect(SERVICE_WORKER_SCOPE).toBe('/i/');
        expect(fake.api.open).toHaveBeenCalledWith(CACHE_NAME);
        expect([...fake.store.keys()].sort()).toEqual([TICKET, ...ASSETS].sort());
    });

    it('ne garde aucun doublon quand la liste en contient', async () => {
        await saveOfflineTicket(TICKET, [...ASSETS, ASSETS[0]]);

        expect(fake.cache.add).toHaveBeenCalledTimes(1 + ASSETS.length);
    });

    it('retire d\'une ancienne copie les fichiers devenus inutiles, mais garde les billets d\'autres invités', async () => {
        fake.store.set('/build/assets/Dashboard-ancien.js', 'ancien');
        fake.store.set('/build/assets/app-abc.js', 'déjà là');
        fake.store.set('/i/autre-jeton/billet', 'billet d\'un autre invité de cet appareil');

        await saveOfflineTicket(TICKET, ASSETS);

        expect(fake.store.has('/build/assets/Dashboard-ancien.js')).toBe(false);
        expect(fake.store.has('/i/autre-jeton/billet')).toBe(true);
        expect(fake.store.has(TICKET)).toBe(true);
        expect(ASSETS.every((path) => fake.store.has(path))).toBe(true);
    });

    it('sait si le billet est gardé', async () => {
        expect(await isOfflineTicketSaved(TICKET)).toBe(false);
        await saveOfflineTicket(TICKET, ASSETS);
        expect(await isOfflineTicketSaved(TICKET)).toBe(true);
    });

    it('efface le cache et ne désinscrit que son propre service worker', async () => {
        await saveOfflineTicket(TICKET, ASSETS);
        await clearOfflineTicket();

        expect(fake.api.delete).toHaveBeenCalledWith(CACHE_NAME);
        expect(unregister).toHaveBeenCalledOnce();
    });

    it('signale un navigateur sans service worker et refuse de garder le billet', async () => {
        vi.stubGlobal('caches', undefined);
        Object.defineProperty(navigator, 'serviceWorker', { configurable: true, value: undefined });
        // `'serviceWorker' in navigator` reste vrai avec une valeur undefined : on le retire vraiment.
        delete (navigator as unknown as Record<string, unknown>).serviceWorker;

        expect(offlineSupported()).toBe(false);
        expect(await isOfflineTicketSaved(TICKET)).toBe(false);
        await expect(saveOfflineTicket(TICKET, ASSETS)).rejects.toThrow('hors ligne');
    });
});
