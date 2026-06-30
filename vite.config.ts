import { globSync } from 'node:fs';
import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

// app.blade.php charge chaque page via `@vite("resources/js/pages/{$page}.vue")`,
// donc chaque page doit être un point d'entrée explicite pour obtenir sa propre
// entrée de manifeste (le chunking automatique des imports dynamiques de Rolldown
// ne suffit pas : il fusionne tout en un seul gros chunk par défaut).
const pageEntries = globSync('resources/js/pages/**/*.vue');

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts', ...pageEntries],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
