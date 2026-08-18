// resources/js/app.js

import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

import NProgress from 'nprogress';
import 'nprogress/nprogress.css';

const appName = import.meta.env.VITE_APP_NAME || 'SuperStart';

// Dark Mode: set sebelum Inertia render (hindari flash)
const darkPref = localStorage.getItem('darkMode');
if (
    darkPref === 'true' ||
    (darkPref === null && window.matchMedia?.('(prefers-color-scheme: dark)').matches)
) {
    document.documentElement.classList.add('dark');
}

// NProgress config (tanpa spinner)
NProgress.configure({
    showSpinner: false,
    speed: 300,
    minimum: 0.1,
    trickleSpeed: 100,
});

import AppLayout from '@/Layouts/AppLayout.vue';

createInertiaApp({
    title: (title) => appName,
    resolve: async (name) => {
        const page = await resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue'));
        
        // [UPDATE: PERSISTENT LAYOUT FIX]
        // Fungsi: Pastikan AppLayout hanya di-assign pada halaman yang memerlukannya.
        // Cara Kerja: Halaman Auth/ dan Splash bersifat standalone (tanpa navbar/sidebar),
        //             sehingga dikecualikan dari pembungkusan AppLayout.
        // Catatan: Splash menggunakan defineOptions({ layout: false }) sebagai fallback,
        //          tetapi pengecekan nama di sini lebih aman dan eksplisit.
        const isStandalone = name.startsWith('Auth/') || name === 'Splash';
        if (page.default.layout === undefined && !isStandalone) {
            page.default.layout = AppLayout;
        }
        
        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        delay: 0, // [UPDATE: PERFORMA - Ubah delay ke 0 agar ada feedback visual instan saat klik]
        color: '#2349F5', 
        showSpinner: false,
        includeCSS: true, // [UPDATE: PERFORMA - Aktifkan CSS agar bar loading terlihat!]
    },
});
