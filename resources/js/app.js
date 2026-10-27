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

// [UPDATE: i18n] Import composable untuk inisialisasi bahasa saat startup
import { useI18n } from '@/composables/useI18n.js';

createInertiaApp({
    title: (title) => appName,
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue');
        
        // [UPDATE: DEVICE DYNAMIC RESOLVER]
        // Secara otomatis mengarahkan ke Pages/Mobile/ atau Pages/Desktop/ jika file khusus tersedia
        let targetName = name;
        if (!name.startsWith('Desktop/') && !name.startsWith('Mobile/')) {
            const isMobile = window.innerWidth < 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
            const prefix = isMobile ? 'Mobile' : 'Desktop';
            const candidatePath = `./Pages/${prefix}/${name}.vue`;
            if (pages[candidatePath]) {
                targetName = `${prefix}/${name}`;
            }
        }

        const page = await resolvePageComponent(`./Pages/${targetName}.vue`, pages);
        
        // [UPDATE: PERSISTENT LAYOUT FIX]
        // Fungsi: Pastikan Layout yang sesuai di-assign pada halaman yang memerlukannya.
        const isStandalone = targetName.includes('Auth/') || targetName.includes('Splash') || targetName.includes('Settings/Language');
        if (page.default.layout === undefined && !isStandalone) {
            if (targetName.startsWith('Mobile/')) {
                const layout = await import('@/Layouts/AppLayoutMobile.vue');
                page.default.layout = layout.default;
            } else if (targetName.startsWith('Desktop/')) {
                const layout = await import('@/Layouts/AppLayoutDesktop.vue');
                page.default.layout = layout.default;
            } else {
                page.default.layout = AppLayout;
            }
        }
        
        return page;
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue);

        // [UPDATE: GLOBAL i18n]
        // Menyediakan fungsi t() ke seluruh komponen Vue secara global (template only)
        app.config.globalProperties.t = useI18n().t;

        app.mount(el);

        // [UPDATE: i18n] Inisialisasi bahasa dari localStorage saat app startup
        // Cara Kerja: Membaca preferensi 'app_locale' dari localStorage,
        //             jika ada locale tersimpan selain id-ID, lazy-load file tersebut.
        //             Ini memastikan bahasa sudah benar sebelum user berinteraksi.
        const { initI18n } = useI18n();
        initI18n();
    },
    progress: {
        delay: 0, // [UPDATE: PERFORMA - Ubah delay ke 0 agar ada feedback visual instan saat klik]
        color: '#2349F5', 
        showSpinner: false,
        includeCSS: true, // [UPDATE: PERFORMA - Aktifkan CSS agar bar loading terlihat!]
    },
});

