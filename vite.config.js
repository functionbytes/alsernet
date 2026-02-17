import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // TODO: Uncomment when Helpdesk React files are created
                // 'modules/Helpdesk/resources/js/helpdesk/app.tsx', // React islands entry point
                // 'modules/Helpdesk/resources/js/helpdesk/widget/widget-entry.tsx', // LiveChat widget
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    esbuild: {
        jsx: 'automatic',
    },
});
