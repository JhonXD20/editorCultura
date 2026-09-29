import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/sass/app.scss',
                'resources/js/app.js',
                'resources/css/editor.css', // <-- ADICIONE ESTA LINHA
                'resources/js/editor.js',   // <-- ADICIONE ESTA LINHA
            ],
            refresh: true,
        }),
    ],
});
