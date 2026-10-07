import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';
import { execSync } from 'child_process';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
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
        tailwindcss(),
        {
            name: 'vite-plugin-ziggy',
            buildStart: () => {
                execSync('php artisan ziggy:generate resources/js/ziggy.js', { stdio: 'inherit' });
            },
        },
    ],
    resolve: {
        alias: {
            // Ensure that we can resolve the ziggy vue module.
            'ziggy-core': path.resolve('vendor/tightenco/ziggy'),
            ziggy: path.resolve(__dirname, './resources/js/shims/ziggy.ts'),
            '@': path.resolve(__dirname, './resources/js'),
        },
    },
});

