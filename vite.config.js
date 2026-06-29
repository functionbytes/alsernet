import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'modules/Helpdesk/resources/js/app.tsx', // React islands entry point
                'modules/HelpdeskChatFlow/resources/js/chatflow-editor.tsx', // ChatFlow visual editor
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
