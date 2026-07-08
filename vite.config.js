import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
// import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
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
                'resources/css/landing/styles.css',
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
                'resources/js/map.js',
                'resources/js/mobile-hub.js',
                'resources/js/staff/fire.js',
                'resources/js/user.js',
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
