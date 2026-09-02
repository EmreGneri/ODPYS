import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            // resources/js/app.js: eski sistemin sayfa-basi mini-Vue
            // formlari (DersFormu, OgretmenFormu vb.) - HIC dokunulmadi.
            // resources/js/spa.js: yeni SPA'nin tek kok giris noktasi -
            // ayri tutuldu ki eski Blade sayfalari SPA gecisi boyunca
            // hic bozulmadan paralel calismaya devam etsin.
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/spa.js'],
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
    ],
});
