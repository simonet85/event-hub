import { describe, expect, it } from 'vitest';
import { resolveTab, tabUrl, type GuestTab } from './guestTabs';

const all: GuestTab[] = ['invitation', 'rsvp', 'billet', 'cadeaux', 'souvenirs'];

describe('resolveTab', () => {
    it("lit le paramètre d'onglet", () => {
        expect(resolveTab('?onglet=billet', '', all)).toBe('billet');
    });

    it('traduit les anciennes ancres vers leur onglet', () => {
        expect(resolveTab('', '#cadeaux', all)).toBe('cadeaux');
        expect(resolveTab('', '#livre-dor', all)).toBe('souvenirs');
        expect(resolveTab('', '#photos', all)).toBe('souvenirs');
        expect(resolveTab('', '#programme', all)).toBe('invitation');
        expect(resolveTab('', '#donnees', all)).toBe('billet');
    });

    it('revient sur Invitation pour un onglet inconnu ou indisponible', () => {
        expect(resolveTab('?onglet=nimporte', '', all)).toBe('invitation');
        expect(resolveTab('?onglet=cadeaux', '', all.filter((t) => t !== 'cadeaux'))).toBe('invitation');
    });
});

describe('tabUrl', () => {
    it("pose l'onglet, retire l'ancre et garde les autres paramètres", () => {
        expect(tabUrl('cadeaux', 'https://x.test/i/abc?a=1#rsvp')).toBe('/i/abc?a=1&onglet=cadeaux');
    });

    it("n'ajoute rien pour l'onglet Invitation", () => {
        expect(tabUrl('invitation', 'https://x.test/i/abc?onglet=rsvp')).toBe('/i/abc');
    });
});
