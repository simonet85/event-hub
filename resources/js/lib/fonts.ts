/** Polices proposées à l'hôte (clé stockée dans `theme_config.font_family`) et pile CSS correspondante. */
export const EVENT_FONTS: Record<string, { label: string; stack: string }> = {
    script: { label: 'Manuscrite', stack: '"Pinyon Script", "Snell Roundhand", cursive' },
    serif: { label: 'Élégante', stack: '"Cormorant Garamond", Cormorant, Georgia, serif' },
    body: { label: 'Moderne', stack: '"DM Sans", system-ui, sans-serif' },
};

export const fontStack = (key: string): string => (EVENT_FONTS[key] ?? EVENT_FONTS.serif).stack;
