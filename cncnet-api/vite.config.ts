import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '0.0.0.0', // bind to all interfaces
        port: 5173,
        hmr: {
            host: 'localhost', // what your browser uses
            port: 5173,
        },
    },
    plugins: [
        laravel({
            input: [
                // Public facing site
                'resources/stylesheets/app.scss',
                'resources/typescript/App.ts',
            ],
            refresh: false,
        }),
    ],
});
