import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/app.js',
                'resources/css/app.css',
                'resources/css/admin.css',
                'resources/css/bs4-compat.css',
                'resources/css/payslip.css',
                'resources/css/public-apply.css',
            ],
            refresh: true,
        }),
    ],
});