import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    // isolate: false réutilise l'environnement jsdom entre fichiers (les tests nettoient leurs globaux eux-mêmes).
    test: { environment: 'jsdom', include: ['resources/js/**/*.test.ts'], isolate: false },
    plugins: [
        laravel({
            input: 'resources/js/app.ts',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
