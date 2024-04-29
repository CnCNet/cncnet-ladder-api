import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
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
