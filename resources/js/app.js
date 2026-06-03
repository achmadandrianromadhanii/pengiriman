// resources/js/app.js

import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';

import NProgress from 'nprogress';

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
    speed: 400,
    minimum: 0.1,
    trickleSpeed: 200,
});

createInertiaApp({
    title: (title) => appName,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        delay: 100,
        showSpinner: false,
        includeCSS: false,
    },
});
