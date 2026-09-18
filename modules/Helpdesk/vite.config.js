import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

// Helpdesk core no longer ships its own JS bundle. The live-chat widget
// lives in modules/HelpdeskLivechat/. This file is kept as a placeholder
// in case the Helpdesk module needs its own assets in the future.
export default defineConfig({
    plugins: [
        react({
            jsxRuntime: 'automatic',
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/assets/js'),
        },
    },
});
