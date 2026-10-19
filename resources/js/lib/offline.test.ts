import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { CACHE_NAME, clearOfflineTicket, isOfflineTicketSaved, loadedStaticPaths, offlineSupported, saveOfflineTicket, SERVICE_WORKER_SCOPE, SERVICE_WORKER_URL } from './offline';

const entries = (...names: string[]) => names.map((name) => ({ name }));

function fakeCaches() {
    const store = new Map<string, unknown>();
    const cache = {
        add: vi.fn(async (path: string) => void store.set(path, 'réponse')),
        match: vi.fn(async (path: string) => store.get(path)),
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
    vi.spyOn(performance, 'getEntriesByType').mockReturnValue(
        entries(
            `${location.origin}/build/assets/app-abc.js`,
            `${location.origin}/build/assets/app-abc.js`,
            `${location.origin}/fonts/dm-sans-normal-latin.woff2`,
            `${location.origin}/photos/12`,
            `${location.origin}/events/mon-evenement`,
            'https://cdn.exemple.test/build/assets/externe.js',
        ) as unknown as PerformanceEntryList,
    );
});

afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
});

describe('billet hors ligne', () => {
    it('ne retient parmi les ressources chargées que les fichiers statiques de l\'interface, sans doublon', () => {
        expect(loadedStaticPaths()).toEqual(['/build/assets/app-abc.js', '/fonts/dm-sans-normal-latin.woff2']);
    });

    it('garde la page du billet et les fichiers statiques seulement, jamais les photos ni l\'espace hôte', async () => {
        await saveOfflineTicket('/i/jeton/billet');

        // Portée étroite : seulement /i/, pas tout le site.
        expect(register).toHaveBeenCalledWith(SERVICE_WORKER_URL, { scope: SERVICE_WORKER_SCOPE });
        expect(SERVICE_WORKER_SCOPE).toBe('/i/');
        expect(fake.api.open).toHaveBeenCalledWith(CACHE_NAME);
        expect([...fake.store.keys()]).toEqual(['/i/jeton/billet', '/build/assets/app-abc.js', '/fonts/dm-sans-normal-latin.woff2']);
    });

    it('sait si le billet est gardé', async () => {
        expect(await isOfflineTicketSaved('/i/jeton/billet')).toBe(false);
        await saveOfflineTicket('/i/jeton/billet');
        expect(await isOfflineTicketSaved('/i/jeton/billet')).toBe(true);
    });

    it('efface le cache et ne désinscrit que son propre service worker', async () => {
        await saveOfflineTicket('/i/jeton/billet');
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
        expect(await isOfflineTicketSaved('/i/jeton/billet')).toBe(false);
        await expect(saveOfflineTicket('/i/jeton/billet')).rejects.toThrow('hors ligne');
    });
});
