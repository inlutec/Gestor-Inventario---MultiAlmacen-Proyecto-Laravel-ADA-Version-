import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            buildDirectory: 'build',
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    base: '/gestionmaterial/build/',
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        manifest: true,
        outDir: 'public/build',
        assetsDir: 'assets',
        target: 'es2015',
        cssCodeSplit: true,
        sourcemap: process.env.NODE_ENV === 'development',
        minify: 'esbuild',
        // Optimizaciones para PWA
        chunkSizeWarningLimit: 1000,
        assetsInlineLimit: 4096,
    },
    experimental: {
        renderBuiltUrl(filename) {
            return '/gestionmaterial/build/' + filename;
        },
    },
    server: {
        hmr: {
            overlay: true,
        },
        // Mejoras para desarrollo móvil
        host: true,
        port: 5173,
        strictPort: false,
    },
    // Optimizaciones para PWA
    define: {
        __VUE_OPTIONS_API__: true,
        __VUE_PROD_DEVTOOLS__: false,
    },
    optimizeDeps: {
        include: [
            'vue',
            'vue-router',
            'pinia',
            'axios',
        ],
    },
});
