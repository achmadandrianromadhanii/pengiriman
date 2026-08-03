// =========================================================================
// SERVICE WORKER — SoftSend PWA
// =========================================================================
// Fungsi: Menangani caching offline dan menampilkan halaman fallback
//         saat user tidak memiliki koneksi internet.
// Cara Kerja:
//   1. INSTALL: Membuat cache bernama 'softsend-v1' dan menyimpan
//      halaman offline (/offline.html) ke dalamnya.
//   2. ACTIVATE: Menghapus cache lama (versi sebelumnya) agar tidak
//      memenuhi storage perangkat.
//   3. FETCH: Jika request navigasi gagal (offline), tampilkan
//      halaman /offline.html dari cache. Request lainnya dibiarkan
//      lewat ke network (network-first strategy).
// Kompatibel: localhost (development) & HTTPS (Vercel production).
// Sesuai dokumentasi: https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API
// =========================================================================

const CACHE_NAME = 'softsend-v1';
const OFFLINE_URL = '/offline.html';

// ── INSTALL: Simpan halaman offline ke cache ─────────────────────────────
// Dipanggil sekali saat service worker pertama kali di-register.
// waitUntil() memastikan SW tidak aktif sebelum cache selesai.
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.add(new Request(OFFLINE_URL, { cache: 'reload' }));
        })
    );
    // Langsung aktifkan SW baru tanpa menunggu SW lama selesai
    self.skipWaiting();
});

// ── ACTIVATE: Bersihkan cache lama ───────────────────────────────────────
// Dipanggil saat SW baru mengambil alih dari SW lama.
// Semua cache yang bukan CACHE_NAME akan dihapus.
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== CACHE_NAME)
                    .map((name) => caches.delete(name))
            );
        }).then(() => {
            // [UPDATE: PERFORMA/BUGFIX] Tangkap error InvalidStateError jika SW belum siap claim clients
            return self.clients.claim().catch(() => {
                console.log('[SW] Clients claim deferred.');
            });
        })
    );
});

// ── FETCH: Network-first, fallback ke offline page ───────────────────────
// Strategi: Selalu coba network dulu.
// Jika gagal DAN request adalah navigasi halaman → tampilkan offline.html.
// Request non-navigasi (API, gambar, CSS, JS) dibiarkan gagal natural.
self.addEventListener('fetch', (event) => {
    // Hanya intercept request navigasi (buka halaman baru)
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(() => {
                return caches.open(CACHE_NAME).then((cache) => {
                    return cache.match(OFFLINE_URL);
                });
            })
        );
    }
    // Request lainnya (API, aset) → biarkan lewat ke network tanpa intercept
});
