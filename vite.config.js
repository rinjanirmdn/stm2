import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/style.css',
                'resources/css/vendor.css',
                'resources/js/app.js',
                'resources/js/pages/main.js',
                'resources/js/pages/gate-status.js',
                'resources/js/pages/gates-monitor.js',
                'resources/js/pages/gates-index.js',
                'resources/js/pages/vendor.js',
                'resources/js/pages/vendor-bookings-index.js',
                'resources/js/pages/dashboard.js',
                'resources/js/pages/slots-index.js',
                'resources/js/pages/slots-create.js',
                'resources/js/pages/slots-edit.js',
                'resources/js/pages/slots-arrival.js',
                'resources/js/pages/slots-complete.js',
                'resources/js/pages/reports-transactions.js',
                'resources/js/pages/unplanned-index.js',
                'resources/js/pages/unplanned-create.js',
                'resources/js/pages/unplanned-edit.js',
                'resources/js/pages/unplanned-complete.js',
                'resources/js/pages/users-index.js',
                'resources/js/pages/users-create.js',
                'resources/js/pages/users-edit.js',
                'resources/js/pages/logs-index.js',
                'resources/js/pages/trucks-index.js',
                'resources/js/pages/admin-bookings-index.js',
                'resources/js/pages/admin-bookings-reschedule.js',
                'resources/js/pages/admin-bookings-show.js'
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
