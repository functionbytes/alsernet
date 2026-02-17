import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    build: {
        outDir: '../../public/build-mailrelay',
        emptyOutDir: true,
        manifest: true,
    },
    plugins: [
        laravel({
            publicDirectory: '../../public',
            buildDirectory: 'build-mailrelay',
            input: [
                __dirname + '/resources/css/app.css',
                __dirname + '/resources/js/app.js'
            ],
            refresh: true,
        }),
    ],
});

export const paths = [
    'modules/Mailrelay/resources/css/app.css',
    'modules/Mailrelay/resources/js/app.js',
];
