import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            // [UPDATE: SINGLE ENTRY POINT]
            // Fungsi: Hanya mendaftarkan satu entry point (app.js) karena app.css sudah di-import di dalamnya
            // Cara Kerja: Vite secara otomatis memproses CSS yang di-import oleh JS, menghindari duplikat preload
            input: ["resources/js/app.js"],
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
    resolve: {
        alias: {
            "@": "/resources/js",
            "ziggy-js": path.resolve("vendor/tightenco/ziggy"),
        },
    },
});
