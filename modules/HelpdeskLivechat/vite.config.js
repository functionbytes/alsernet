import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
    build: {
        outDir: '../../public/build-helpdesklivechat',
        emptyOutDir: true,
        manifest: true,
        // Fail build if any chunk exceeds the budget (gzipped target ~50KB)
        chunkSizeWarningLimit: 80,
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'resources/assets/js/widget/widget-entry.tsx'),
                'widget-embed': resolve(__dirname, 'resources/assets/js/widget-embed.ts'),
            },
            output: {
                entryFileNames: (chunkInfo) => {
                    if (chunkInfo.name === 'widget-embed') {
                        return 'widget.js';
                    }
                    return 'widget/[name].js';
                },
                chunkFileNames: () => 'widget/chunks/[name]-[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'widget/[name][extname]';
                    }
                    return 'widget/assets/[name]-[hash][extname]';
                },
            },
        },
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-helpdesklivechat',
            input: [
                __dirname + '/resources/assets/js/widget/widget-entry.tsx',
                __dirname + '/resources/assets/js/widget-embed.ts',
            ],
            refresh: true,
        }),
        react({
            jsxRuntime: 'automatic',
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/assets/js'),
        },
    },
    define: {
        __WIDGET_BUILD_VERSION__: JSON.stringify(Date.now().toString(36)),
    },
    esbuild: {
        jsx: 'automatic',
    },
});
