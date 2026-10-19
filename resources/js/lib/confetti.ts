/**
 * Confettis : une courte animation de célébration (canvas, sans dépendance) pour la confirmation de présence et
 * le don confirmé. Elle respecte `prefers-reduced-motion`, ne capte jamais la souris ni le focus, est masquée aux
 * lecteurs d'écran (l'information reste portée par le texte de confirmation) et se nettoie toute seule.
 */
export interface ConfettiOptions {
    colors?: string[];
    /** Durée totale en millisecondes. */
    duration?: number;
    /** Nombre de particules (par défaut selon la largeur de l'écran). */
    count?: number;
    // Injectables pour les tests.
    requestFrame?: (callback: (time: number) => void) => number;
    cancelFrame?: (id: number) => void;
    random?: () => number;
}

export interface ConfettiHandle {
    cancel: () => void;
}

const FALLBACK_COLORS = ['#4e6b48', '#7a5c1c', '#a4c19c', '#e8dcc8'];

export const prefersReducedMotion = (): boolean => typeof window !== 'undefined' && window.matchMedia?.('(prefers-reduced-motion: reduce)').matches === true;

/** Couleurs du thème de l'événement affiché (accent, or, tons de surface), avec repli sur les jetons du Design System. */
export function themeColors(): string[] {
    const root = document.querySelector('[data-theme]') ?? document.documentElement;
    const style = getComputedStyle(root);
    const read = (name: string) => style.getPropertyValue(name).trim();
    const colors = [read('--accent'), read('--gold'), read('--surface-300'), read('--line-strong')].filter((c) => /^#|^rgb|^color-mix/.test(c));

    return colors.length >= 2 ? colors : FALLBACK_COLORS;
}

interface Particle {
    x: number;
    y: number;
    vx: number;
    vy: number;
    size: number;
    rotation: number;
    spin: number;
    color: string;
    round: boolean;
}

/** Lance l'animation ; renvoie null (sans rien afficher) si elle n'est pas souhaitée ou impossible. */
export function launchConfetti(options: ConfettiOptions = {}): ConfettiHandle | null {
    if (typeof document === 'undefined' || prefersReducedMotion() || document.hidden) return null;

    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');
    if (!context) return null;

    const random = options.random ?? Math.random;
    const requestFrame = options.requestFrame ?? ((cb) => window.requestAnimationFrame(cb));
    const cancelFrame = options.cancelFrame ?? ((id) => window.cancelAnimationFrame(id));
    const duration = options.duration ?? 2600;
    const colors = options.colors?.length ? options.colors : themeColors();
    const width = window.innerWidth;
    const height = window.innerHeight;
    const count = options.count ?? (width < 480 ? 60 : 120);
    const ratio = Math.min(window.devicePixelRatio || 1, 2);

    canvas.dataset.confetti = '';
    canvas.setAttribute('aria-hidden', 'true');
    canvas.style.cssText = 'position:fixed;inset:0;width:100%;height:100%;pointer-events:none;z-index:60';
    canvas.width = Math.round(width * ratio);
    canvas.height = Math.round(height * ratio);
    context.scale(ratio, ratio);
    document.body.appendChild(canvas);

    const particles: Particle[] = Array.from({ length: count }, () => ({
        x: width / 2 + (random() - 0.5) * width * 0.3,
        y: height * 0.4,
        vx: (random() - 0.5) * 14,
        vy: -(6 + random() * 9),
        size: 6 + random() * 6,
        rotation: random() * Math.PI * 2,
        spin: (random() - 0.5) * 0.4,
        color: colors[Math.floor(random() * colors.length)],
        round: random() < 0.3,
    }));

    let frame = 0;
    let start: number | null = null;
    let last = 0;
    let finished = false;

    const finish = () => {
        if (finished) return;
        finished = true;
        cancelFrame(frame);
        canvas.remove();
    };

    const step = (time: number) => {
        if (finished) return;
        start ??= time;
        const elapsed = time - start;
        const dt = Math.min((time - (last || time)) / 16.67 || 1, 3);
        last = time;

        if (elapsed >= duration) return finish();

        context.clearRect(0, 0, width, height);
        // Les confettis s'effacent sur le dernier tiers de la durée.
        context.globalAlpha = Math.min(1, (duration - elapsed) / (duration / 3));

        for (const p of particles) {
            p.vy += 0.38 * dt;
            p.vx *= 0.99;
            p.x += p.vx * dt;
            p.y += p.vy * dt;
            p.rotation += p.spin * dt;

            context.save();
            context.translate(p.x, p.y);
            context.rotate(p.rotation);
            context.fillStyle = p.color;
            if (p.round) {
                context.beginPath();
                context.arc(0, 0, p.size / 2, 0, Math.PI * 2);
                context.fill();
            } else {
                context.fillRect(-p.size / 2, -p.size / 4, p.size, p.size / 2);
            }
            context.restore();
        }

        frame = requestFrame(step);
    };

    frame = requestFrame(step);

    return { cancel: finish };
}

/** Célébration aux couleurs de la page. À appeler après une confirmation, jamais pendant un paiement. */
export const celebrate = (options: ConfettiOptions = {}): ConfettiHandle | null => launchConfetti(options);
