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
                // Bandeja de conversaciones de Helpdesk: split hoy de un monolito de
                // 7.138 líneas en 5 ficheros por responsabilidad (jQuery clásico, sin
                // import/export). core.js expone helpers en window (openModal,
                // escapeHtml, ...) que list/thread/panel/extras consumen tal cual, así
                // que el ORDEN de estas 5 líneas debe respetarse en index.blade.php
                // (@vite conserva el orden dado, y <script type="module"> sin async
                // se ejecuta en orden de documento — ver conversations-core.js).
                'modules/Helpdesk/resources/js/conversations-core.js',
                'modules/Helpdesk/resources/js/conversations-list.js',
                'modules/Helpdesk/resources/js/conversations-thread.js',
                'modules/Helpdesk/resources/js/conversations-panel.js',
                'modules/Helpdesk/resources/js/conversations-extras.js',
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
