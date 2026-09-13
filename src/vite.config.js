import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/public.css',
                'resources/css/global.css',
                'resources/css/room.css',
                'resources/css/admin.css',
                'resources/css/superadmin.css',
                'resources/js/app.js',
                'resources/js/public.js',
                'resources/js/global.js',
                'resources/js/room.js',
                'resources/js/admin.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
