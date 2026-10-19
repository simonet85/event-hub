import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { beforeEach, describe, expect, it, vi } from 'vitest';

/**
 * Comportement réel du service worker public/billet-sw.js : le script est exécuté tel quel, avec un faux
 * `self`, un faux cache et un faux réseau.
 */
const source = readFileSync(join(process.cwd(), 'public', 'billet-sw.js'), 'utf8');

type FakeResponse = { ok: boolean; status: number; body: string; clone: () => FakeResponse };
const response = (status: number, body = 'réseau'): FakeResponse => ({ ok: status >= 200 && status < 300, status, body, clone: () => response(status, body) });

const ORIGIN = 'https://exemple.test';
const TICKET = '/i/jeton/billet';

let listeners: Record<string, (event: unknown) => void>;
let store: Map<string, FakeResponse>;
let cache: { match: ReturnType<typeof vi.fn>; put: ReturnType<typeof vi.fn>; delete: ReturnType<typeof vi.fn> };
let network: ReturnType<typeof vi.fn>;
let self: { location: { origin: string }; skipWaiting: ReturnType<typeof vi.fn>; clients: { claim: ReturnType<typeof vi.fn> }; addEventListener: (type: string, fn: (event: unknown) => void) => void };

beforeEach(() => {
    listeners = {};
    store = new Map();
    cache = {
        match: vi.fn(async (path: string) => store.get(path)),
        put: vi.fn(async (path: string, res: FakeResponse) => void store.set(path, res)),
        delete: vi.fn(async (path: string) => store.delete(path)),
    };
    network = vi.fn(async () => response(200));
    self = {
        location: { origin: ORIGIN },
        skipWaiting: vi.fn(),
        clients: { claim: vi.fn(async () => {}) },
        addEventListener: (type, fn) => void (listeners[type] = fn),
    };

    new Function('self', 'caches', 'fetch', source)(self, { open: async () => cache }, network);
});

/** Simule un événement `fetch` du navigateur ; renvoie la réponse fournie à respondWith, ou null si le worker ne s'en mêle pas. */
async function fetchEvent(url: string, init: { method?: string; mode?: string } = {}): Promise<FakeResponse | null> {
    let provided: Promise<FakeResponse> | null = null;
    listeners.fetch({
        request: { method: init.method ?? 'GET', mode: init.mode ?? 'navigate', url: url.startsWith('http') ? url : ORIGIN + url },
        respondWith: (p: Promise<FakeResponse>) => void (provided = p),
    });

    return provided ? await provided : null;
}

describe('billet-sw.js', () => {
    it('s\'active immédiatement', () => {
        listeners.install({});
        const waitUntil = vi.fn();
        listeners.activate({ waitUntil });

        expect(self.skipWaiting).toHaveBeenCalled();
        expect(self.clients.claim).toHaveBeenCalled();
        expect(waitUntil).toHaveBeenCalled();
    });

    it('ne s\'occupe ni des requêtes non GET, ni des autres origines, ni des autres pages', async () => {
        expect(await fetchEvent(TICKET, { method: 'POST' })).toBeNull();
        expect(await fetchEvent('https://autre.test' + TICKET)).toBeNull();
        expect(await fetchEvent('/events/mon-evenement')).toBeNull();
        expect(await fetchEvent('/i/jeton')).toBeNull();
        expect(await fetchEvent('/i/jeton/donations')).toBeNull();
        expect(await fetchEvent('/photos/12', { mode: 'no-cors' })).toBeNull();
        expect(network).not.toHaveBeenCalled();
    });

    it('sert le billet depuis le réseau sans rien garder tant que l\'invité n\'a pas demandé la copie', async () => {
        const res = await fetchEvent(TICKET);

        expect(res?.body).toBe('réseau');
        expect(cache.put).not.toHaveBeenCalled();
        expect(store.size).toBe(0);
    });

    it('rafraîchit la copie gardée quand le réseau répond', async () => {
        store.set(TICKET, response(200, 'ancienne copie'));
        network.mockResolvedValueOnce(response(200, 'nouvelle version'));

        const res = await fetchEvent(TICKET);

        expect(res?.body).toBe('nouvelle version');
        expect(store.get(TICKET)?.body).toBe('nouvelle version');
    });

    it('sert la copie gardée quand le réseau est coupé', async () => {
        store.set(TICKET, response(200, 'copie locale'));
        network.mockRejectedValueOnce(new TypeError('hors ligne'));

        expect((await fetchEvent(TICKET))?.body).toBe('copie locale');
    });

    it('échoue normalement hors ligne quand aucune copie n\'a été gardée', async () => {
        network.mockRejectedValueOnce(new TypeError('hors ligne'));

        await expect(fetchEvent(TICKET)).rejects.toThrow('hors ligne');
    });

    it.each([404, 410])('supprime la copie dès que le serveur répond %i (événement archivé ou invité retiré)', async (status) => {
        store.set(TICKET, response(200, 'copie locale'));
        network.mockResolvedValueOnce(response(status, 'introuvable'));

        const res = await fetchEvent(TICKET);

        expect(res?.status).toBe(status);
        expect(cache.delete).toHaveBeenCalledWith(TICKET);
        expect(store.has(TICKET)).toBe(false);
    });

    it('garde la copie quand le serveur a seulement un incident passager (5xx)', async () => {
        store.set(TICKET, response(200, 'copie locale'));
        network.mockResolvedValueOnce(response(503, 'indisponible'));

        await fetchEvent(TICKET);

        expect(cache.delete).not.toHaveBeenCalled();
        expect(store.get(TICKET)?.body).toBe('copie locale');
    });

    it('sert les fichiers statiques gardés sans réseau, et laisse passer les autres sans les garder', async () => {
        store.set('/build/assets/app-abc.js', response(200, 'fichier gardé'));

        expect((await fetchEvent('/build/assets/app-abc.js', { mode: 'cors' }))?.body).toBe('fichier gardé');
        expect(network).not.toHaveBeenCalled();

        expect((await fetchEvent('/fonts/dm-sans-normal-latin.woff2', { mode: 'cors' }))?.body).toBe('réseau');
        expect(network).toHaveBeenCalledOnce();
        expect(cache.put).not.toHaveBeenCalled();
    });
});
