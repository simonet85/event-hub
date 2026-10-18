import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.ts',
    ],
    theme: {
        extend: {
            // Design System « Papier & lumière » : les couleurs lisent les variables CSS (clair/sombre, ev-*).
            colors: {
                'surface-100': 'var(--surface-100)',
                'surface-200': 'var(--surface-200)',
                'surface-300': 'var(--surface-300)',
                ink: 'var(--ink)',
                'ink-muted': 'var(--ink-muted)',
                line: 'var(--line)',
                'line-strong': 'var(--line-strong)',
                gold: 'var(--gold)',
                accent: 'var(--accent)',
                'accent-mariage': 'var(--accent-mariage)',
                'accent-bapteme': 'var(--accent-bapteme)',
                'accent-anniversaire': 'var(--accent-anniversaire)',
                'accent-fete': 'var(--accent-fete)',
                'on-accent': 'var(--on-accent)',
                success: 'var(--success)',
                warning: 'var(--warning)',
                danger: 'var(--danger)',
            },
            keyframes: {
                'fade-up': { from: { opacity: '0', transform: 'translateY(8px)' }, to: { opacity: '1', transform: 'none' } },
            },
            fontFamily: {
                sans: ['"DM Sans"', 'system-ui', '-apple-system', '"Segoe UI"', ...defaultTheme.fontFamily.sans],
                display: ['"Cormorant Garamond"', 'Cormorant', 'Georgia', ...defaultTheme.fontFamily.serif],
                script: ['"Pinyon Script"', '"Snell Roundhand"', 'cursive'],
                // Choix de police d'un événement (theme_config.font_family) : seulement les trois familles du système.
                serif: ['"Cormorant Garamond"', 'Cormorant', 'Georgia', ...defaultTheme.fontFamily.serif],
                body: ['"DM Sans"', 'system-ui', ...defaultTheme.fontFamily.sans],
            },
            fontSize: {
                'display-xl': ['64px', { lineHeight: '60px', fontWeight: '500' }],
                'display-lg': ['44px', { lineHeight: '46px', fontWeight: '500' }],
                heading: ['30px', { lineHeight: '34px', fontWeight: '600' }],
                'body-lg': ['18px', { lineHeight: '28px' }],
                label: ['14px', { lineHeight: '20px', fontWeight: '600' }],
                caption: ['13px', { lineHeight: '18px' }],
            },
            // Angles presque droits (esprit papier) : 2 px, photos 6 px. Les anciens utilitaires rounded-* suivent.
            borderRadius: {
                sm: 'var(--radius-sm)',
                DEFAULT: 'var(--radius-sm)',
                md: 'var(--radius-sm)',
                lg: 'var(--radius-md)',
                xl: 'var(--radius-sm)',
                '2xl': 'var(--radius-sm)',
                '3xl': 'var(--radius-sm)',
                pill: 'var(--radius-pill)',
            },
            boxShadow: {
                card: 'var(--shadow-card)',
            },
            minHeight: {
                tap: 'var(--tap)',
            },
            minWidth: {
                tap: 'var(--tap)',
            },
            spacing: {
                tap: 'var(--tap)',
            },
        },
    },
    plugins: [forms],
};
