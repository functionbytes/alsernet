import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
    build: {
        outDir: '../../public/build-chat',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                // Widget main bundle
                'widget/main': resolve(__dirname, 'resources/assets/js/widget/main.tsx'),
                // Widget embed script
                'widget-embed': resolve(__dirname, 'resources/assets/js/widget-embed.ts'),
            },
            output: {
                // Ensure widget.js is output with consistent name
                entryFileNames: (chunkInfo) => {
                    if (chunkInfo.name === 'widget-embed') {
                        return 'widget.js'; // Public embed script
                    }
                    return 'widget/[name].js';
                },
                chunkFileNames: 'widget/chunks/[name]-[hash].js',
                assetFileNames: 'widget/assets/[name]-[hash][extname]',
            },
        },
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-chat',
            input: [
                __dirname + '/resources/assets/sass/app.scss',
                __dirname + '/resources/assets/js/app.js',
                __dirname + '/resources/assets/js/widget/main.tsx',
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
    esbuild: {
        jsx: 'automatic',
    },
});
// Scen all resources for assets file. Return array
//function getFilePaths(dir) {
//    const filePaths = [];
//
//    function walkDirectory(currentPath) {
//        const files = readdirSync(currentPath);
//        for (const file of files) {
//            const filePath = join(currentPath, file);
//            const stats = statSync(filePath);
//            if (stats.isFile() && !file.startsWith('.')) {
//                const relativePath = 'Modules/Chat/'+relative(__dirname, filePath);
//                filePaths.push(relativePath);
//            } else if (stats.isDirectory()) {
//                walkDirectory(filePath);
//            }
//        }
//    }
//
//    walkDirectory(dir);
//    return filePaths;
//}

//const __filename = fileURLToPath(import.meta.url);
//const __dirname = dirname(__filename);

//const assetsDir = join(__dirname, 'resources/assets');
//export const paths = getFilePaths(assetsDir);


//export const paths = [
//    'Modules/Chat/resources/assets/sass/app.scss',
//    'Modules/Chat/resources/assets/js/app.js',
//];
