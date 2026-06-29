import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    build: {
        outDir: '../../public/build-engagement',
        emptyOutDir: true,
        manifest: true,
        chunkSizeWarningLimit: 80,
        rollupOptions: {
            input: {
                sdk: resolve(__dirname, 'resources/assets/sdk/index.ts'),
                'sdk-worker': resolve(__dirname, 'resources/assets/sdk-worker/worker.ts'),
            },
            output: {
                entryFileNames: (chunkInfo) => {
                    if (chunkInfo.name === 'sdk') return 'sdk.js';
                    if (chunkInfo.name === 'sdk-worker') return 'sdk-worker.js';
                    return '[name].js';
                },
                chunkFileNames: 'chunks/[name]-[hash].js',
                assetFileNames: 'assets/[name]-[hash][extname]',
            },
        },
    },
});
