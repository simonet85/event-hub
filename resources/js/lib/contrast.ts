import { translate as __ } from '@/i18n';
/** Contraste WCAG entre deux couleurs hexadécimales (#rrggbb). */

const channel = (value: number): number => {
    const c = value / 255;

    return c <= 0.03928 ? c / 12.92 : ((c + 0.055) / 1.055) ** 2.4;
};

export const isHexColor = (hex: string): boolean => /^#[0-9a-fA-F]{6}$/.test(hex);

export function luminance(hex: string): number {
    const n = parseInt(hex.slice(1), 16);

    return 0.2126 * channel((n >> 16) & 255) + 0.7152 * channel((n >> 8) & 255) + 0.0722 * channel(n & 255);
}

export function contrastRatio(a: string, b: string): number {
    const [light, dark] = [luminance(a), luminance(b)].sort((x, y) => y - x);

    return (light + 0.05) / (dark + 0.05);
}

/** Seuils du Design System : 4.5:1 pour le texte, 3:1 pour une bordure ou un anneau de focus. */
export const MIN_TEXT_CONTRAST = 4.5;
export const MIN_BORDER_CONTRAST = 3;

/** Couleur de texte à poser sur un fond d'accent : celle des deux `on-accent` qui contraste le mieux. */
export function onAccent(accent: string): string {
    const light = '#fffaf2';
    const dark = '#1d1711';

    return contrastRatio(accent, light) >= contrastRatio(accent, dark) ? light : dark;
}

export interface ContrastReport {
    ratio: number;
    textOk: boolean;
    borderOk: boolean;
}

/** Évalue une couleur d'accent posée sur un fond : texte (4.5:1) et bordure (3:1). */
export function checkContrast(foreground: string, background: string): ContrastReport | null {
    if (!isHexColor(foreground) || !isHexColor(background)) {
        return null;
    }

    const ratio = contrastRatio(foreground, background);

    return { ratio, textOk: ratio >= MIN_TEXT_CONTRAST, borderOk: ratio >= MIN_BORDER_CONTRAST };
}

/** Fond sombre du Design System, utilisé par le thème Fête tant que l'hôte n'a pas choisi son propre fond. */
export const DARK_SURFACE = '#1d1711';

/** Fond réellement affiché derrière le texte d'un événement (mode sombre inclus). */
export function effectiveSurface(secondary: string, presetSecondary: string | undefined, dark: boolean | undefined): string {
    const customized = presetSecondary !== undefined && secondary.toLowerCase() !== presetSecondary.toLowerCase();

    return dark && !customized ? DARK_SURFACE : secondary;
}

export interface ThemeAudit {
    /** Couleur principale (texte, titres d'accent) sur le fond. */
    text: ContrastReport;
    /** Meilleur rapport possible pour le texte posé sur un bouton d'accent (clair ou foncé). */
    buttonRatio: number;
    buttonOk: boolean;
    /** Messages à montrer à l'hôte ; vide si le thème est lisible. */
    problems: string[];
}

const formatRatio = (ratio: number): string => ratio.toFixed(1).replace('.', ',');

/**
 * Audit d'un thème d'événement : la couleur principale doit atteindre 4.5:1 comme texte sur le fond
 * (3:1 au minimum pour une bordure), et le texte des boutons d'accent doit atteindre 4.5:1.
 */
export function auditTheme(accent: string, surface: string): ThemeAudit | null {
    const text = checkContrast(accent, surface);

    if (!text) {
        return null;
    }

    const on = onAccent(accent);
    const buttonRatio = contrastRatio(accent, on);
    const buttonOk = buttonRatio >= MIN_TEXT_CONTRAST;
    const problems: string[] = [];

    if (!text.textOk) {
        problems.push(
            text.borderOk
                ? __('Contraste insuffisant : :ratio entre la couleur principale et le fond (4,5:1 requis pour le texte). Choisissez une couleur plus foncée ou un fond plus clair.', { ratio: `${formatRatio(text.ratio)}:1` })
                : __('Contraste insuffisant : :ratio entre la couleur principale et le fond (4,5:1 requis pour le texte, 3:1 pour une bordure). Choisissez une couleur plus foncée ou un fond plus clair.', { ratio: `${formatRatio(text.ratio)}:1` }),
        );
    }
    if (!buttonOk) {
        problems.push(__('Le texte des boutons serait peu lisible sur cette couleur (:ratio, 4,5:1 requis). Éclaircissez ou assombrissez-la.', { ratio: `${formatRatio(buttonRatio)}:1` }));
    }

    return { text, buttonRatio, buttonOk, problems };
}
