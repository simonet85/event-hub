import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import EnvelopeIntro from './EnvelopeIntro.vue';

// `playing` passe à vrai dans onMounted : il faut laisser Vue rendre avant d'observer le DOM.
const mountIntro = async (token = 'tok') => {
    const w = mount(EnvelopeIntro, { props: { token, guestName: 'Awa Koné' }, attachTo: document.body });
    await nextTick();

    return w;
};
const intro = () => document.querySelector('[data-testid="envelope-intro"]');

beforeEach(() => {
    vi.useFakeTimers();
    window.localStorage.clear();
    window.location.hash = '';
    vi.stubGlobal('matchMedia', () => ({ matches: false }));
});

afterEach(() => {
    document.body.innerHTML = '';
    window.localStorage.clear();
    vi.useRealTimers();
    vi.unstubAllGlobals();
    vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);
});

describe('EnvelopeIntro', () => {
    it('s\'affiche à la première visite, masquée aux lecteurs d\'écran, avec le nom de l\'invité', async () => {
        await mountIntro();

        expect(intro()).not.toBeNull();
        expect(intro()?.getAttribute('aria-hidden')).toBe('true');
        expect(intro()?.textContent).toContain('Awa Koné');
    });

    it('se retire toute seule à la fin de l\'animation', async () => {
        await mountIntro();
        await vi.advanceTimersByTimeAsync(3000);

        expect(intro()).toBeNull();
    });

    it('ne se rejoue pas à la visite suivante', async () => {
        const first = await mountIntro();
        first.unmount();

        await mountIntro();
        expect(intro()).toBeNull();
    });

    it.each(['pointerdown', 'keydown', 'wheel', 'touchmove'])('se referme au geste « %s » sans attendre la fin', async (type) => {
        await mountIntro();

        window.dispatchEvent(new Event(type));
        await nextTick();
        expect(intro()?.classList.contains('is-leaving')).toBe(true);

        await vi.advanceTimersByTimeAsync(300);
        expect(intro()).toBeNull();
    });

    it('n\'empêche jamais le geste d\'atteindre la page', async () => {
        await mountIntro();
        const event = new Event('pointerdown', { cancelable: true });

        window.dispatchEvent(event);

        expect(event.defaultPrevented).toBe(false);
    });

    it('ne s\'affiche pas avec prefers-reduced-motion', async () => {
        vi.stubGlobal('matchMedia', (query: string) => ({ matches: query.includes('reduce') }));
        await mountIntro();

        expect(intro()).toBeNull();
    });

    it('ne s\'affiche pas quand l\'adresse vise une ancre', async () => {
        window.location.hash = '#rsvp';
        await mountIntro();

        expect(intro()).toBeNull();
    });

    it('retire ses écouteurs de gestes quand elle disparaît', async () => {
        const remove = vi.spyOn(window, 'removeEventListener');
        const w = await mountIntro();
        w.unmount();

        expect(remove.mock.calls.map(([name]) => name)).toEqual(expect.arrayContaining(['pointerdown', 'keydown', 'wheel', 'touchmove']));
        remove.mockRestore();
    });
});
