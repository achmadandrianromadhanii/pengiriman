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
    // Fungsi: Memecah library JavaScript berat ke file terpisah (chunk) agar tidak
    //         ikut ter-bundle ke dalam file JS utama (app.js).
    // Alasan: Tanpa ini, browser HP Android harus mengunduh + mengurai SEMUA library
    //         (~600KB total: apexcharts, sweetalert2, pusher-js, leaflet) sebelum
    //         halaman pertama bisa tampil. Dengan code-splitting, setiap library
    //         hanya di-download saat halaman yang membutuhkannya dibuka.
    // Hasil: Halaman Login hanya mengunduh ~80KB (Vue + Inertia + Axios).
    //        Dashboard menambah ~450KB (ApexCharts) di background setelah tampil.
    //        Ini membuat First Contentful Paint dan LCP sangat cepat.
    build: {
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor-charts': ['apexcharts', 'vue3-apexcharts'],
                    'vendor-swal': ['sweetalert2'],
                    'vendor-echo': ['pusher-js', 'laravel-echo'],
                    'vendor-map': ['leaflet'],
                },
            },
        },
    },
});
