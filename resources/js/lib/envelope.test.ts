import { describe, expect, it } from 'vitest';
import { ENVELOPE_PREFIX, markEnvelopePlayed, shouldPlayEnvelope } from './envelope';

const memory = () => {
    const data = new Map<string, string>();

    return { getItem: (k: string) => data.get(k) ?? null, setItem: (k: string, v: string) => void data.set(k, v) };
};

describe('ouverture d\'enveloppe : quand la jouer', () => {
    it('joue à la première visite, pas aux suivantes', () => {
        const storage = memory();

        expect(shouldPlayEnvelope('tok', { storage, reducedMotion: false, hash: '' })).toBe(true);
        markEnvelopePlayed('tok', storage);
        expect(storage.getItem(ENVELOPE_PREFIX + 'tok')).toBe('1');
        expect(shouldPlayEnvelope('tok', { storage, reducedMotion: false, hash: '' })).toBe(false);
    });

    it('est propre à chaque invité', () => {
        const storage = memory();
        markEnvelopePlayed('a', storage);

        expect(shouldPlayEnvelope('b', { storage, reducedMotion: false, hash: '' })).toBe(true);
    });

    it('ne joue pas quand l\'invité demande moins d\'animations', () => {
        expect(shouldPlayEnvelope('tok', { storage: memory(), reducedMotion: true, hash: '' })).toBe(false);
    });

    it('ne joue pas quand on arrive sur une ancre (lien direct vers le RSVP)', () => {
        expect(shouldPlayEnvelope('tok', { storage: memory(), reducedMotion: false, hash: '#rsvp' })).toBe(false);
        expect(shouldPlayEnvelope('tok', { storage: memory(), reducedMotion: false, hash: '#' })).toBe(true);
    });

    it('joue sans erreur quand le stockage est indisponible', () => {
        const broken = { getItem: () => { throw new Error('bloqué'); }, setItem: () => { throw new Error('bloqué'); } };

        expect(shouldPlayEnvelope('tok', { storage: null, reducedMotion: false, hash: '' })).toBe(true);
        expect(shouldPlayEnvelope('tok', { storage: broken, reducedMotion: false, hash: '' })).toBe(false);
        expect(() => markEnvelopePlayed('tok', broken)).not.toThrow();
    });
});
