// bootstrap.js
// [UPDATE: OPTIMASI CRITICAL PATH]
// Fungsi: Hanya memuat axios (ringan, ~15KB) saat startup.
// Alasan: Sebelumnya echo.js dimuat langsung di sini, menyebabkan pusher-js (~100KB)
//         ikut ter-download dan membuka koneksi WebSocket sebelum halaman selesai render.
// Cara Kerja: Echo/Pusher sekarang di-lazy-load via fungsi initEcho() di echo.js,
//             hanya dipanggil oleh komponen yang benar-benar butuh real-time (Dashboard).
// Hasil: Waktu buka halaman pertama (Login/Welcome) berkurang ~100KB = super cepat.

import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
