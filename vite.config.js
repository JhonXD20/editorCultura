
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',

                'resources/css/totem.css',
                'resources/css/editor.css',
                'resources/css/publico.css',

                'resources/js/editor.js',
                'resources/js/publico.js'
            ],

            refresh: true
        })
    ]
});
