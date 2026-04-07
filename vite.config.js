import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin/style.css',
                'resources/js/admin/login.js',
                'resources/js/admin/dashboard.js'
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
