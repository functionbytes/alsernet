import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // El editor visual de ChatFlow (React+TSX) es propio del módulo y
                // no pasa por resources/js/app.js: sin esta entrada, Vite nunca lo
                // compilaba y editor.blade.php->@vite() lanzaba
                // "Unable to locate file in Vite manifest" — un 500 en toda
                // pantalla de edición de flujo, no solo en el test.
                'modules/HelpdeskChatFlow/resources/js/chatflow-editor.tsx',
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
