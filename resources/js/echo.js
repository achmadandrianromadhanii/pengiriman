// echo.js
// [UPDATE: LAZY-LOAD ECHO + PUSHER]
// Fungsi: Menunda pemuatan library Echo & Pusher hingga benar-benar dibutuhkan.
// Alasan: pusher-js (~100KB) + laravel-echo membuka koneksi WebSocket langsung saat halaman dibuka.
//         Di HP Android ini memblokir render awal dan membuat loading terasa sangat lama.
// Cara Kerja: Fungsi initEcho() hanya dipanggil saat komponen yang membutuhkan real-time
//             sudah di-mount (misal Dashboard). Jika sudah diinisialisasi, langsung return.
// Hasil: Halaman Login, Tracking, Laporan dsb TIDAK memuat Pusher sama sekali = ngebut.

let echoInitialized = false;

export async function initEcho() {
    // Cegah inisialisasi ganda
    if (echoInitialized || window.Echo) return;
    echoInitialized = true;

    // Dynamic import — hanya di-download saat dipanggil
    const [{ default: Echo }, { default: Pusher }] = await Promise.all([
        import('laravel-echo'),
        import('pusher-js'),
    ]);

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: import.meta.env.VITE_PUSHER_APP_KEY,
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'ap1',
        forceTLS: true,
    });
}
