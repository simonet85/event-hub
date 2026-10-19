import { prefersReducedMotion } from '@/lib/confetti';

/**
 * Ouverture d'enveloppe : jouée une seule fois par invité et par appareil. Elle est ignorée quand l'invité demande
 * moins d'animations, ou qu'il arrive sur une ancre précise (lien direct vers #rsvp, #cadeaux…) : il vient pour agir.
 */
export const ENVELOPE_PREFIX = 'eventhub-envelope:';

export interface EnvelopeContext {
    storage?: Pick<Storage, 'getItem' | 'setItem'> | null;
    reducedMotion?: boolean;
    hash?: string;
}

function defaultStorage(): Storage | null {
    try {
        return window.localStorage;
    } catch {
        return null; // navigation privée ou stockage bloqué
    }
}

export function shouldPlayEnvelope(token: string, context: EnvelopeContext = {}): boolean {
    const reducedMotion = context.reducedMotion ?? prefersReducedMotion();
    const hash = context.hash ?? window.location.hash;
    if (reducedMotion || hash.length > 1) return false;

    try {
        const storage = context.storage === undefined ? defaultStorage() : context.storage;

        // Sans stockage disponible on joue : au pire l'animation se rejoue, ce qui est sans conséquence.
        return !storage || storage.getItem(ENVELOPE_PREFIX + token) === null;
    } catch {
        return false;
    }
}

export function markEnvelopePlayed(token: string, storage: EnvelopeContext['storage'] = defaultStorage()): void {
    try {
        storage?.setItem(ENVELOPE_PREFIX + token, '1');
    } catch {
        // Stockage indisponible : l'animation pourra se rejouer, sans conséquence.
    }
}
