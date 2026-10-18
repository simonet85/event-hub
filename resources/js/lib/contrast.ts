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
