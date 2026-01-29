import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/style.css',
                'resources/css/vendor.css',
                'resources/js/app.js',
                'resources/js/main.js',
                'resources/js/gate-status.js',
                'resources/js/vendor.js'
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        origin: process.env.VITE_DEV_ORIGIN || (process.env.VITE_DEV_HOST ? `http://${process.env.VITE_DEV_HOST}:5173` : undefined),
        cors: true,
        headers: {
            'Access-Control-Allow-Origin': '*',
            'Access-Control-Allow-Methods': 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers': '*',
        },
        strictPort: true,
        hmr: {
            host: process.env.VITE_HMR_HOST || process.env.VITE_DEV_HOST || undefined,
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
