# 🏛️ Panduan Arsitektur & Standar Pengembangan (Dual-UI Desktop & Mobile)

Dokumen ini adalah **Single Source of Truth** mengenai arsitektur sistem, aturan pemisahan UI, dan optimasi performa untuk proyek **Aplikasi Pengiriman (SuperStart)** berbasis **Laravel + Inertia.js + Vue 3**.

---

## 1. Arsitektur Dual-UI (Server-Side Device Routing)

Aplikasi ini menggunakan 1 basis kode backend Laravel dengan 2 pengalaman antarmuka yang terisolasi secara mandiri:
1. **Desktop View** (dioptimalkan untuk layar lebar, keyboard/mouse, data tabular, dan multi-kolom).
2. **Mobile View** (dioptimalkan untuk layar sentuh HP/tablet, navigasi bawah / *bottom navigation*, kartu KPI ringkas, dan beban rendering ringan).

### Diagram Alur Permintaan

```text
Request dari Browser (HP / Laptop)
             │
             ▼
┌────────────────────────────────────────────────────────┐
│             Laravel Application Kernel                 │
│      App\Services\Inertia\ResponsiveResponseFactory     │
│       Deteksi User-Agent (Server-Side PHP)             │
└───────────────────────────┬────────────────────────────┘
                            │
             ┌──────────────┴──────────────┐
             ▼                             ▼
       Perangkat Mobile               Perangkat Desktop
  (Android, iPhone, iPad)           (Windows, Mac, Linux)
             │                             │
             ▼                             ▼
Inertia::render('Mobile/...')   Inertia::render('Desktop/...')
             │                             │
             ▼                             ▼
  Browser hanya mendownload      Browser hanya mendownload
     chunk JS khusus Mobile        chunk JS khusus Desktop
```

### Mekanisme Kerja `ResponsiveResponseFactory`

- Didaftarkan sebagai singleton di [`app/Providers/AppServiceProvider.php`](file:///c:/xampp/htdocs/pengiriman/app/Providers/AppServiceProvider.php).
- Meng-extend `Inertia\ResponseFactory`.
- Saat controller mengeksekusi `Inertia::render('Dashboard/Index', $data)`:
  1. Jika User-Agent terdeteksi sebagai perangkat mobile (`/Mobile|Android|iPhone|iPad|.../i`):
     - Memeriksa apakah `resources/js/Pages/Mobile/Dashboard/Index.vue` ada di disk.
     - Jika ada, response yang dikirim ke Inertia adalah `'Mobile/Dashboard/Index'`.
  2. Jika Desktop:
     - Memeriksa apakah `resources/js/Pages/Desktop/Dashboard/Index.vue` ada di disk.
     - Jika ada, response yang dikirim ke Inertia adalah `'Desktop/Dashboard/Index'`.
  3. Jika halaman tidak memiliki varian khusus (halaman bersama seperti `Splash.vue` atau `Profile/Security.vue`), otomatis memakai nama komponen aslinya tanpa prefix.
- Memiliki `static $resolvedCache` untuk mencegah *overhead* I/O pada pemanggilan berulang.

---

## 2. Struktur Direktori `resources/js/Pages`

```text
resources/js/Pages/
├── Desktop/                  <-- Khusus Tampilan Desktop PC
│   ├── Auth/Login.vue
│   ├── Dashboard/Index.vue
│   ├── Laporan/Index.vue
│   ├── Pengiriman/ (Create, Index, Show)
│   ├── Profile/Edit.vue
│   ├── Settings/Index.vue
│   ├── Tarif/Index.vue
│   └── Tracking/ (Public, Search)
│
├── Mobile/                   <-- Khusus Tampilan Mobile Smartphone
│   ├── Auth/Login.vue
│   ├── Dashboard/Index.vue
│   ├── Laporan/Index.vue
│   ├── Pengiriman/ (Create, Index, Show)
│   ├── Profile/Edit.vue
│   ├── Settings/Index.vue
│   ├── Tarif/Index.vue
│   └── Tracking/ (Public, Search)
│
└── Shared / Bersama/         <-- Komponen yang dipakai bersama
    ├── Splash.vue
    ├── Profile/ (DeleteAccount, Security, Partials/...)
    └── System/ (About, Cache, Storage)
```

---

## 3. Aturan Bundling & Code Splitting (Vite)

Dikonfigurasi di [`vite.config.js`](file:///c:/xampp/htdocs/pengiriman/vite.config.js):

1. **`vendor-core`**: Berisi Vue core, `@inertiajs/vue3`, dan Axios. Berukuran kecil (~100 kB mentah, ~32 kB gzipped) sehingga First Contentful Paint (FCP) sangat instan.
2. **`vendor-charts`**: Berisi `chart.js` dan `vue-chartjs` (~240 kB mentah). **Hanya di-download ketika halaman membutuhkan grafik**. Halaman tanpa chart tidak mengunduh chunk ini.
3. **`vendor-maps`**: Berisi Leaflet & GIS assets (~148 kB). Hanya di-download ketika membuka halaman peta/tracking.
4. **`vendor-icons`**: Berisi Lucide Icons yang digunakan bersama.
5. **Dilarang memasang ApexCharts**: Library `apexcharts` dan `vue3-apexcharts` telah dihapus secara permanen karena ukurannya yang mencapai 500+ kB dan konsumsi RAM yang tinggi di browser mobile. Gunakan selalu `Chart.js` + `vue-chartjs`.

---

## 4. Standar UI/UX & Kinerja Mobile (Android & iOS)

Untuk mempertahankan skor Google Lighthouse 95-100% dan rendering 60 FPS pada HP Android menengah ke bawah:

### A. Larangan CSS `backdrop-filter: blur(...)`
- **Jangan gunakan** class `backdrop-blur-*` pada kartu atau elemen scrollable di tampilan mobile.
- Efek blur memaksa GPU browser HP melakukan rasterisasi ulang pada setiap frame scroll, menyebabkan stutter/lag.
- **Gunakan warna solid dengan transparansi RGBA**, misalnya:
  - Background overlay: `bg-black/60` (BUKAN `bg-black/40 backdrop-blur-sm`).
  - Card glassmorphism: `bg-white/20 border border-white/20` (tanpa `backdrop-blur`).

### B. Konfigurasi Grafik (Chart.js)
- Gunakan komponen pembungkus:
  - [`resources/js/Components/ChartJsLine.vue`](file:///c:/xampp/htdocs/pengiriman/resources/js/Components/ChartJsLine.vue)
  - [`resources/js/Components/ChartJsDoughnut.vue`](file:///c:/xampp/htdocs/pengiriman/resources/js/Components/ChartJsDoughnut.vue)
- Animasi chart pada mobile dibatasi maksimal 350-400ms (`animation: { duration: 350 }`) untuk menghemat baterai dan siklus CPU/GPU.
- `responsive: true`, `maintainAspectRatio: false` dengan container berdimensi pasti (`h-[140px]`, `h-[160px]`, dsb.).

### C. Realtime (Pusher & Laravel Echo)
- Semua data pengiriman, status, dan metrik dashboard terhubung secara realtime via Laravel Echo (`resources/js/echo.js`).
- Event listeners harus di-cleanup pada lifecycle hook `onUnmounted()` di setiap komponen Vue untuk mencegah *memory leak* di HP.

---

## 5. Panduan Pemeliharaan (Maintenance Rules untuk AI & Developer)

Ketika ada permintaan perubahan atau fitur baru di kemudian hari:

1. **Jika perubahan hanya untuk Mobile**:
   - Hanya edit file di dalam `resources/js/Pages/Mobile/...` atau komponen khusus mobile.
   - **JANGAN** menyentuh file di `resources/js/Pages/Desktop/...` agar tampilan desktop tetap stabil.
2. **Jika perubahan hanya untuk Desktop**:
   - Hanya edit file di dalam `resources/js/Pages/Desktop/...`.
   - **JANGAN** menyentuh file di `resources/js/Pages/Mobile/...`.
3. **Jika menambahkan halaman baru**:
   - Jika halaman tersebut memiliki desain berbeda untuk desktop dan mobile, buat kedua file:
     - `resources/js/Pages/Desktop/{Fitur}/{Nama}.vue`
     - `resources/js/Pages/Mobile/{Fitur}/{Nama}.vue`
   - Controller cukup memanggil: `Inertia::render('{Fitur}/{Nama}', $data)` tanpa perlu `if (isMobile)`. Sistem server-side resolver akan otomatis mengarahkan ke file yang tepat.
4. **Verifikasi Build**:
   - Setiap kali menambah dependensi atau mengubah komponen penting, jalankan `npm run build` untuk memverifikasi tidak ada pemutusan dependensi atau peningkatan bundle size yang tidak wajar.
