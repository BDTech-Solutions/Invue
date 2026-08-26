import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import invue from '@invue/vite-plugin';

export default defineConfig({
    resolve: {
        // invue/* packages resolve straight from vendor/, which Composer
        // installs as a symlink into packages/*. Without this, Vite
        // realpath's through the symlink before resolving further bare
        // imports (e.g. @inertiajs/vue3) inside those files, and looks for
        // node_modules next to packages/* instead of this app's own.
        preserveSymlinks: true,
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        invue(),
    ],
});
