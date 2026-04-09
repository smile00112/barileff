import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';
import path from 'path';

export default defineConfig({
    plugins: [
        vue(),
        VitePWA({
            registerType: 'autoUpdate',
            outDir: '../../public/manager',
            base: '/manager/',
            scope: '/manager/',
            manifest: {
                name: 'Manager App',
                short_name: 'Manager',
                description: 'Warehouse order management',
                theme_color: '#2563eb',
                background_color: '#ffffff',
                display: 'standalone',
                orientation: 'portrait',
                start_url: '/manager/',
                scope: '/manager/',
                icons: [
                    {
                        src: '/manager/icons/icon-192.png',
                        sizes: '192x192',
                        type: 'image/png',
                    },
                    {
                        src: '/manager/icons/icon-512.png',
                        sizes: '512x512',
                        type: 'image/png',
                    },
                ],
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg}'],
                navigateFallback: '/manager/',
                navigateFallbackDenylist: [/^\/manager\/api\//],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        outDir: '../../public/manager',
        emptyOutDir: true,
        rollupOptions: {
            input: path.resolve(__dirname, 'resources/js/main.js'),
        },
    },
    base: '/manager/',
});
