import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import Marquee from './Marquee.vue';
import Reveal from './Reveal.vue';
import ScrollExpandHero from './ScrollExpandHero.vue';

afterEach(() => vi.unstubAllGlobals());

describe('Reveal', () => {
    it('reste visible sans IntersectionObserver', () => {
        vi.stubGlobal('IntersectionObserver', undefined);
        const w = mount(Reveal, { slots: { default: 'Bonjour' } });
        expect(w.classes()).toContain('is-visible');
    });

    it('devient visible quand il entre dans l\'écran', async () => {
        let callback: (e: { isIntersecting: boolean }[]) => void = () => {};
        vi.stubGlobal(
            'IntersectionObserver',
            class {
                constructor(cb: typeof callback) {
                    callback = cb;
                }
                observe() {}
                disconnect() {}
            },
        );
        const w = mount(Reveal, { slots: { default: 'Bonjour' } });
        expect(w.classes()).not.toContain('is-visible');
        callback([{ isIntersecting: true }]);
        await w.vm.$nextTick();
        expect(w.classes()).toContain('is-visible');
    });
});

describe('Marquee', () => {
    it('duplique la piste et masque la copie aux lecteurs d\'écran', () => {
        const w = mount(Marquee, { props: { items: ['A', 'B'] } });
        const tracks = w.findAll('.marquee-track');
        expect(tracks).toHaveLength(2);
        expect(tracks[1].attributes('aria-hidden')).toBe('true');
        expect(tracks[0].text()).toContain('A');
    });
});

describe('ScrollExpandHero', () => {
    const stubMatchMedia = (matches: boolean) => vi.stubGlobal('matchMedia', () => ({ matches }));

    it('écarte les moitiés selon la progression du scroll', async () => {
        stubMatchMedia(false);
        vi.stubGlobal('innerHeight', 500);
        const w = mount(ScrollExpandHero, { attachTo: document.body });
        const root = w.get('[data-testid="scroll-hero"]').element as HTMLElement;
        Object.defineProperty(root, 'offsetHeight', { value: 1500 });
        root.getBoundingClientRect = () => ({ top: -500 }) as DOMRect;

        window.dispatchEvent(new Event('scroll'));
        await w.vm.$nextTick();

        expect((w.vm as unknown as { progress: number }).progress).toBeCloseTo(0.5);
        expect(w.get('[data-testid="hero-left"]').attributes('style')).toContain('translateX(-50%)');
        w.unmount();
    });

    it('affiche directement le contenu si l\'utilisateur réduit les animations', async () => {
        stubMatchMedia(true);
        const w = mount(ScrollExpandHero);
        await w.vm.$nextTick();
        expect(w.find('[data-testid="hero-left"]').exists()).toBe(false);
        expect((w.vm as unknown as { progress: number }).progress).toBe(1);
    });
});
