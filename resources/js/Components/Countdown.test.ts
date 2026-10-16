import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Countdown from './Countdown.vue';

describe('Countdown', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.setSystemTime(new Date('2026-01-01T00:00:00Z'));
    });
    afterEach(() => vi.useRealTimers());

    it('affiche le temps restant', () => {
        const w = mount(Countdown, { props: { target: '2026-01-03T04:05:06Z' } });
        expect(w.get('[data-testid="countdown-jours"]').text()).toBe('02');
        expect(w.get('[data-testid="countdown-heures"]').text()).toBe('04');
        expect(w.get('[data-testid="countdown-minutes"]').text()).toBe('05');
        expect(w.get('[data-testid="countdown-secondes"]').text()).toBe('06');
    });

    it('se met à jour chaque seconde', async () => {
        const w = mount(Countdown, { props: { target: '2026-01-01T00:00:10Z' } });
        await vi.advanceTimersByTimeAsync(3000);
        expect(w.get('[data-testid="countdown-secondes"]').text()).toBe('07');
    });

    it('affiche le message du grand jour quand la date est passée', () => {
        const w = mount(Countdown, { props: { target: '2025-12-31T00:00:00Z' } });
        expect(w.find('[data-testid="countdown-finished"]').exists()).toBe(true);
        expect(w.find('[data-testid="countdown"]').exists()).toBe(false);
    });
});
