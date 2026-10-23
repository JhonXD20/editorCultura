
import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/admin-notifications.css',
                'resources/js/admin-notifications.js',
                'resources/js/admin-navigation.js',
                'resources/css/admin-theme.css',
                'resources/css/login.css',
                'resources/js/login.js',
                'resources/js/theme.js',

                'resources/css/totem.css',
                'resources/css/publico.css',

                'resources/js/grapes-editor.js',
                'resources/js/publico.js',

                'resources/css/content-manager.css',
                'resources/js/content-manager.js',

                'resources/css/totens.css',
                'resources/js/totens.js',

                'resources/css/relatorios.css',
                'resources/js/relatorios.js',

                'resources/css/admin.css',
                'resources/js/admin.js'
            ],

            refresh: true
        })
    ]
});
