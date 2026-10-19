import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { useHostColorScheme } from './colorScheme';

type Listener = (event: { matches: boolean }) => void;

function stubScheme(dark: boolean) {
    const listeners = new Set<Listener>();
    const media = {
        matches: dark,
        addEventListener: (_: string, l: Listener) => listeners.add(l),
        removeEventListener: (_: string, l: Listener) => listeners.delete(l),
    };
    vi.stubGlobal('matchMedia', () => media);

    return { listeners, emit: (matches: boolean) => listeners.forEach((l) => l({ matches })) };
}

const Host = defineComponent({
    setup() {
        const scheme = useHostColorScheme();

        return () => h('p', scheme.value);
    },
});

const html = document.documentElement;

// Le compteur de mises en page montées est global : chaque test démonte ce qu'il a monté.
const mounted: ReturnType<typeof mount>[] = [];
const mountHost = () => {
    const w = mount(Host);
    mounted.push(w);

    return w;
};

afterEach(() => {
    mounted.splice(0).forEach((w) => w.unmount());
    document.body.innerHTML = '';
    delete html.dataset.theme;
    html.style.removeProperty('color-scheme');
    vi.unstubAllGlobals();
    vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);
});

describe('mode sombre de l\'espace organisateur', () => {
    it('applique les jetons sombres quand le système préfère le sombre', () => {
        stubScheme(true);
        const w = mountHost();

        expect(html.dataset.theme).toBe('dark');
        expect(html.style.colorScheme).toBe('dark');
        expect(w.text()).toBe('dark');
    });

    it('reste clair sinon', () => {
        stubScheme(false);
        mountHost();

        expect(html.dataset.theme).toBe('light');
        expect(html.style.colorScheme).toBe('light');
    });

    it('suit un changement de préférence en direct', () => {
        const media = stubScheme(false);
        const w = mountHost();

        media.emit(true);
        expect(html.dataset.theme).toBe('dark');

        media.emit(false);
        expect(html.dataset.theme).toBe('light');
        w.unmount();
    });

    it('nettoie <html> et ses écouteurs au départ de l\'espace organisateur', () => {
        const media = stubScheme(true);
        const w = mountHost();
        expect(media.listeners.size).toBe(1);

        w.unmount();

        expect(html.dataset.theme).toBeUndefined();
        expect(html.style.colorScheme).toBe('');
        expect(media.listeners.size).toBe(0);
    });

    it('survit à une navigation (nouvelle page montée avant que l\'ancienne ne parte)', () => {
        stubScheme(true);
        const first = mountHost();
        const second = mountHost();

        first.unmount();
        expect(html.dataset.theme).toBe('dark');

        second.unmount();
        expect(html.dataset.theme).toBeUndefined();
    });

    it('fonctionne sans matchMedia (anciens navigateurs, tests)', () => {
        vi.stubGlobal('matchMedia', undefined);

        expect(() => mountHost()).not.toThrow();
        expect(html.dataset.theme).toBe('light');
    });
});
