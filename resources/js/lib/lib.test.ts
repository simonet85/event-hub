import { describe, expect, it } from 'vitest';
import { auditTheme, checkContrast, contrastRatio, DARK_SURFACE, effectiveSurface, MIN_BORDER_CONTRAST, MIN_TEXT_CONTRAST, onAccent } from './contrast';
import { fcfa } from './format';

// Jetons de DESIGN-SYSTEM.md (clair / sombre).
const light = { surface: '#fbf6ee', card: '#f3ebdd', field: '#e8dcc8', ink: '#2a1f17', muted: '#66554a', lineStrong: '#8a7560', gold: '#7a5c1c' };
const dark = { surface: '#1d1711', card: '#272019', field: '#332a21', ink: '#f4ece0', muted: '#c4b5a3', lineStrong: '#8c7a68', gold: '#d9b36a' };
const accents = {
    mariage: ['#4e6b48', '#a4c19c'],
    bapteme: ['#42698f', '#9bbfe2'],
    anniversaire: ['#a0432a', '#e59a80'],
    fete: ['#7a3d6f', '#d3a3c8'],
};

describe('contraste', () => {
    it('calcule le rapport WCAG', () => {
        expect(contrastRatio('#000000', '#ffffff')).toBeCloseTo(21, 0);
        expect(contrastRatio('#777777', '#777777')).toBeCloseTo(1, 5);
    });

    it.each([
        ['clair', light, 0],
        ['sombre', dark, 1],
    ])('le texte atteint 4.5:1 sur les surfaces (%s)', (_name, t, index) => {
        for (const bg of [t.surface, t.card, t.field]) {
            expect(contrastRatio(t.ink, bg)).toBeGreaterThanOrEqual(MIN_TEXT_CONTRAST);
            expect(contrastRatio(t.muted, bg)).toBeGreaterThanOrEqual(MIN_TEXT_CONTRAST);
        }
        for (const bg of [t.surface, t.card]) {
            expect(contrastRatio(t.gold, bg)).toBeGreaterThanOrEqual(MIN_TEXT_CONTRAST);
        }
        for (const [name, pair] of Object.entries(accents)) {
            expect(contrastRatio(pair[index], t.surface), `accent ${name}`).toBeGreaterThanOrEqual(MIN_TEXT_CONTRAST);
        }
    });

    it('les bordures de champs atteignent 3:1 dans les deux thèmes', () => {
        expect(contrastRatio(light.lineStrong, light.surface)).toBeGreaterThanOrEqual(MIN_BORDER_CONTRAST);
        expect(contrastRatio(dark.lineStrong, dark.surface)).toBeGreaterThanOrEqual(MIN_BORDER_CONTRAST);
    });

    it('le texte posé sur un accent (on-accent) atteint 4.5:1', () => {
        for (const pair of Object.values(accents)) {
            expect(contrastRatio('#fffaf2', pair[0])).toBeGreaterThanOrEqual(MIN_TEXT_CONTRAST);
            expect(contrastRatio('#1d1711', pair[1])).toBeGreaterThanOrEqual(MIN_TEXT_CONTRAST);
        }
    });

    it('choisit la couleur de texte la plus lisible sur un accent personnalisé', () => {
        expect(onAccent('#1a1a1a')).toBe('#fffaf2');
        expect(onAccent('#f5e6a8')).toBe('#1d1711');
    });

    it('signale une couleur trop pâle sur le fond et ignore les valeurs invalides', () => {
        const pale = checkContrast('#b08968', '#fdf6ee');
        expect(pale?.textOk).toBe(false);
        expect(checkContrast('#4e6b48', '#fbf6ee')?.textOk).toBe(true);
        expect(checkContrast('pas-une-couleur', '#fbf6ee')).toBeNull();
    });
});

describe('fcfa', () => {
    it('formate « 25 000 F CFA » avec des espaces insécables', () => {
        expect(fcfa(25000)).toBe('25 000 F CFA');
        expect(fcfa(620000)).toBe('620 000 F CFA');
        expect(fcfa(0)).toBe('0 F CFA');
    });
});

describe('audit de thème', () => {
    it.each(Object.entries(accents))('le préréglage %s est lisible en clair et en sombre', (_name, [lightAccent, darkAccent]) => {
        expect(auditTheme(lightAccent, light.surface)?.problems).toEqual([]);
        expect(auditTheme(darkAccent, DARK_SURFACE)?.problems).toEqual([]);
    });

    it('signale une couleur principale trop pâle', () => {
        const audit = auditTheme('#b08968', '#fdf6ee');
        expect(audit?.text.textOk).toBe(false);
        expect(audit?.problems[0]).toContain('Contraste insuffisant');
        expect(audit?.problems[0]).toContain('3:1 pour une bordure');
    });

    it("signale une couleur dont aucun texte de bouton n'est lisible", () => {
        const audit = auditTheme('#7a7a7a', '#ffffff');
        expect(audit?.buttonOk).toBe(false);
        expect(audit?.problems.some((p) => p.includes('boutons'))).toBe(true);
    });

    it('ne signale rien pour un thème personnalisé lisible', () => {
        expect(auditTheme('#123456', '#ffffff')?.problems).toEqual([]);
    });

    it('ignore les valeurs qui ne sont pas des couleurs complètes', () => {
        expect(auditTheme('#12', '#ffffff')).toBeNull();
    });

    it("évalue le thème Fête sur son fond sombre tant que l'hôte garde le fond du préréglage", () => {
        expect(effectiveSurface('#fbf6ee', '#fbf6ee', true)).toBe(DARK_SURFACE);
        expect(effectiveSurface('#ffeedd', '#fbf6ee', true)).toBe('#ffeedd');
        expect(effectiveSurface('#fbf6ee', '#fbf6ee', false)).toBe('#fbf6ee');
    });

    it('un accent foncé choisi pour le thème Fête est signalé sur le fond sombre', () => {
        const audit = auditTheme('#7a3d6f', effectiveSurface('#fbf6ee', '#fbf6ee', true));
        expect(audit?.text.textOk).toBe(false);
    });
});
