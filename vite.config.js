import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

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
            // [UPDATE: ALIAS PATH UTAMA]
            // Fungsi: Shortcut "@" agar import komponen menjadi singkat dan rapi
            // Contoh: import Navbar from '@/Components/Navbar.vue'
            "@": "/resources/js",
            // [UPDATE: ZIGGY RESOLUSI OTOMATIS]
            // Fungsi: ziggy-js di-resolve dari node_modules (package.json) secara otomatis
            // Penjelasan: Tidak perlu alias manual ke vendor/ karena sudah ada di npm dependencies
            // Ini juga menjamin kompatibilitas saat build di Vercel (npm build jalan duluan sebelum composer install)
        },
    },
    // [UPDATE: OPTIMASI BUILD — CODE SPLITTING]
    // Fungsi: Vite secara otomatis akan melakukan code-splitting berkat dynamic imports
    //         di dalam Dashboard/Index.vue. Menghapus manualChunks akan membiarkan 
    //         Vite dan Rollup mengelola dependensi (seperti Vue) dengan benar 
    //         tanpa membuat dependensi silang yang memaksa chart di-load di seluruh halaman.
    build: {
        rollupOptions: {
            output: {
                // Biarkan Vite memecah chunks secara otomatis
            },
        },
    },
});
