import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { launchConfetti, prefersReducedMotion, themeColors } from './confetti';

const fakeContext = () => ({
    scale: vi.fn(),
    clearRect: vi.fn(),
    save: vi.fn(),
    restore: vi.fn(),
    translate: vi.fn(),
    rotate: vi.fn(),
    beginPath: vi.fn(),
    arc: vi.fn(),
    fill: vi.fn(),
    fillRect: vi.fn(),
    fillStyle: '',
    globalAlpha: 1,
});

/** Boucle d'animation pilotée à la main : chaque `step(t)` joue une image au temps t (ms). */
function manualLoop() {
    let pending: ((time: number) => void) | null = null;

    return {
        requestFrame: (callback: (time: number) => void) => {
            pending = callback;

            return 1;
        },
        cancelFrame: vi.fn(() => {
            pending = null;
        }),
        step: (time: number) => pending?.(time),
        get waiting() {
            return pending !== null;
        },
    };
}

const canvases = () => document.querySelectorAll('canvas[data-confetti]');
const reducedMotion = (reduced: boolean) => vi.stubGlobal('matchMedia', (query: string) => ({ matches: reduced && query.includes('reduce') }));

let getContextSpy: { mockRestore: () => void } | undefined;
let context: ReturnType<typeof fakeContext>;

beforeEach(() => {
    context = fakeContext();
    getContextSpy = vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue(context as unknown as CanvasRenderingContext2D);
    reducedMotion(false);
    Object.defineProperty(window, 'innerWidth', { configurable: true, value: 1024 });
    Object.defineProperty(window, 'innerHeight', { configurable: true, value: 768 });
    Object.defineProperty(document, 'hidden', { configurable: true, value: false });
});

afterEach(() => {
    document.querySelectorAll('canvas').forEach((c) => c.remove());
    getContextSpy?.mockRestore();
    vi.unstubAllGlobals();
});

describe('confettis', () => {
    it('ne lance rien quand l\'utilisateur demande de réduire les animations', () => {
        reducedMotion(true);

        expect(prefersReducedMotion()).toBe(true);
        expect(launchConfetti()).toBeNull();
        expect(canvases()).toHaveLength(0);
    });

    it('ne lance rien quand l\'onglet est masqué ou que le canvas n\'est pas disponible', () => {
        Object.defineProperty(document, 'hidden', { configurable: true, value: true });
        expect(launchConfetti()).toBeNull();

        Object.defineProperty(document, 'hidden', { configurable: true, value: false });
        vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue(null);
        expect(launchConfetti()).toBeNull();
        expect(canvases()).toHaveLength(0);
    });

    it('affiche un canvas décoratif : masqué aux lecteurs d\'écran, sans capter la souris', () => {
        const loop = manualLoop();
        const handle = launchConfetti({ ...loop });

        expect(handle).not.toBeNull();
        const canvas = canvases()[0] as HTMLCanvasElement;
        expect(canvas.getAttribute('aria-hidden')).toBe('true');
        expect(canvas.style.pointerEvents).toBe('none');
        expect(canvas.style.position).toBe('fixed');
        expect(canvas.tabIndex).toBe(-1);
    });

    it('dessine les particules à chaque image, puis retire le canvas au bout de la durée', () => {
        const loop = manualLoop();
        launchConfetti({ ...loop, duration: 1000, count: 10, colors: ['#4e6b48', '#7a5c1c'] });

        loop.step(0);
        loop.step(500);

        expect(context.clearRect).toHaveBeenCalled();
        expect(context.fillRect.mock.calls.length + context.arc.mock.calls.length).toBe(20);
        expect(canvases()).toHaveLength(1);

        loop.step(1000);

        expect(canvases()).toHaveLength(0);
        expect(loop.waiting).toBe(false);
    });

    it('utilise les couleurs demandées', () => {
        const loop = manualLoop();
        const used = new Set<string>();
        Object.defineProperty(context, 'fillStyle', { set: (v: string) => used.add(v), get: () => '' });

        launchConfetti({ ...loop, count: 40, colors: ['#111111', '#222222'] });
        loop.step(0);
        loop.step(100);

        expect([...used].every((c) => ['#111111', '#222222'].includes(c))).toBe(true);
    });

    it('peut être annulé : le canvas disparaît et l\'animation s\'arrête', () => {
        const loop = manualLoop();
        const handle = launchConfetti({ ...loop });

        handle?.cancel();

        expect(canvases()).toHaveLength(0);
        expect(loop.cancelFrame).toHaveBeenCalled();
        expect(() => handle?.cancel()).not.toThrow();
    });

    it('réduit le nombre de particules sur petit écran', () => {
        const drawn = (width: number) => {
            Object.defineProperty(window, 'innerWidth', { configurable: true, value: width });
            const ctx = fakeContext();
            vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue(ctx as unknown as CanvasRenderingContext2D);
            const loop = manualLoop();
            launchConfetti({ ...loop, random: () => 0.9 });
            loop.step(0);
            document.querySelectorAll('canvas').forEach((c) => c.remove());

            return ctx.fillRect.mock.calls.length + ctx.arc.mock.calls.length;
        };

        expect(drawn(375)).toBe(60);
        expect(drawn(1280)).toBe(120);
    });

    it('lit les couleurs du thème de la page, avec un repli sur les jetons du Design System', () => {
        expect(themeColors().length).toBeGreaterThanOrEqual(2);

        const root = document.createElement('div');
        root.setAttribute('data-theme', 'light');
        root.style.setProperty('--accent', '#42698f');
        root.style.setProperty('--gold', '#7a5c1c');
        document.body.appendChild(root);

        expect(themeColors()).toContain('#42698f');
        root.remove();
    });
});
