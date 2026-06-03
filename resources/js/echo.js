import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

// [UPDATE: KONFIGURASI PUSHER FRONTEND]
// Fungsi: Menginisialisasi koneksi real-time ke server Pusher Cloud, bukan lagi ke Reverb lokal.
// Ini dijamin stabil dan sangat optimal untuk infrastruktur Serverless seperti Vercel tanpa membebani LCP/CLS/INP.
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1',
    forceTLS: true,
});
