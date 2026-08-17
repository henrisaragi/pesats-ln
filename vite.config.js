import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
            'resources/js/app.js',
            'resources/js/onboarding-tour.js',
            'resources/js/components/dropzone.js',
            'resources/js/components/input-spin.js',
            'resources/js/components/timepicker.js',
            'resources/js/pages/apps-calendar.js',
            'resources/js/pages/apps-ecommerce-orders.js',
            'resources/js/pages/apps-mailbox.js',
            'resources/js/pages/chat.js',
            'resources/js/pages/dashboard-analytics.js',
            'resources/js/pages/dashboard-ecommerce.js',
            'resources/js/pages/dashboard-email.js',
            'resources/js/pages/dashboard-hr.js',
            'resources/js/pages/documents-index.js',
            'resources/js/pages/kecamatan-index.js',
            'resources/js/pages/kelurahan-index.js',
            'resources/js/pages/komoditas-entitas-index.js',
            'resources/js/pages/kuota-nasional-index.js',
            'resources/js/pages/kuota-perusahaan-index.js',
            'resources/js/pages/kuota-saya-index.js',
            'resources/js/pages/landing.js',
            'resources/js/pages/pages-coming-soon.js',
            'resources/js/pages/permohonan-show.js',
            'resources/js/pages/ports-index.js',
            'resources/js/pages/users-index.js',
            'resources/js/vendor.js'
        ],
            refresh: true,
        }),
        tailwindcss(),
    ],
});
