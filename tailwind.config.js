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
            keyframes: {
                'fade-up': { from: { opacity: '0', transform: 'translateY(8px)' }, to: { opacity: '1', transform: 'none' } },
            },
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', '"Playfair Display"', ...defaultTheme.fontFamily.serif],
                script: ['"Great Vibes"', 'cursive'],
                serif: ['"Playfair Display"', ...defaultTheme.fontFamily.serif],
                body: ['Montserrat', ...defaultTheme.fontFamily.sans],
            },
        },
    },
    plugins: [forms],
};
