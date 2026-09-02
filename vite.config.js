import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
// import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    build: {
        // Keep prior hashed assets so already-open tabs do not receive a 404
        // immediately after a new production bundle is generated.
        emptyOutDir: false,
        // Agora stays lazy-loaded; its remaining core module is vendor-owned and
        // sits just above Vite's default warning after the supporting code is split.
        chunkSizeWarningLimit: 600,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('/node_modules/@agora-js/media/')) return 'agora-media';
                    if (id.includes('/node_modules/@agora-js/protocol/')) return 'agora-protocol';
                    if (id.includes('/node_modules/@agora-js/report/')) return 'agora-report';
                    if (id.includes('/node_modules/@agora-js/shared/')) return 'agora-shared';
                    if (id.includes('/node_modules/agora-rte-extension')) return 'agora-extension';
                    if (id.includes('/node_modules/agora-rtc-sdk-ng/')) return 'agora-core';
                    return undefined;
                },
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/admin/announcements.css',
                'resources/css/admin/dashboard.css',
                'resources/css/admin/management.css',
                'resources/css/admin/resource-management.css',
                'resources/css/chief/dashboard.css',
                'resources/css/citizen/dashboard.css',
                'resources/css/citizen/safety.css',
                'resources/css/firefighters/dashboard.css',
                'resources/css/firefighters/dashboard-shell.css',
                'resources/css/firefighters/portal.css',
                'resources/css/firefighters/station-shell.css',
                'resources/css/firefighters/my-station.css',
                'resources/css/staff/announcements.css',
                'resources/css/landing/styles.css',
                'resources/css/voice-calls.css',
                'resources/css/duty-roster.css',
                'resources/js/admin/announcements.js',
                'resources/js/admin/getnearest.js',
                'resources/js/admin/management.js',
                'resources/js/admin/reports.js',
                'resources/js/admin/resource-management.js',
                'resources/js/admin/resources.js',
                'resources/js/app.js',
                'resources/js/barangay/dashboard.js',
                'resources/js/chief/announcements.js',
                'resources/js/chief/management.js',
                'resources/js/citizen/safety.js',
                'resources/js/firefighter/announcements.js',
                'resources/js/firefighter/dashboard.js',
                'resources/js/firefighter/dashboard-shell.js',
                'resources/js/firefighter/station-shell.js',
                'resources/js/functions.js',
                'resources/js/landing/main.js',
                'resources/js/location-accuracy.js',
                'resources/js/map.js',
                'resources/js/mobile-hub.js',
                'resources/js/unified-notifications.js',
                'resources/js/staff/fire.js',
                'resources/js/user.js',
                'resources/js/voice-calls.js',
                'resources/js/duty-roster.js',
            ],
            refresh: true,
        }),
        // tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
