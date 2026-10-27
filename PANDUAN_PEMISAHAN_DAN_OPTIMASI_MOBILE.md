# PANDUAN LENGKAP ARSITEKTUR & PEMISAHAN TAMPILAN MOBILE - DESKTOP
## Project: SoftSend (Laravel 12 + Vue 3 + Inertia.js)

Dokumen ini adalah **SOP (Standard Operating Procedure) dan Roadmap Teknis Lengkap** untuk memisahkan tampilan Mobile dan Desktop ke dalam file independen di dalam satu project yang sama, guna menghilangkan beban rendering (*lag/patah-patah*) di HP Android **tanpa mengubah tampilan visual sedikit pun dan tanpa merusak fungsi yang sudah berjalan**.

---

## 1. Prinsip Utama Pengerjaan (Golden Rules)

1. **Zero Visual Regression**: Tampilan di HP Android dan Laptop/PC harus **100% sama persis** dengan tampilan saat ini (warna, font, margin, animasi, ikon, dan layout tidak boleh berubah).
2. **Zero Functional Breakage**: Seluruh logika bisnis, database query, autentikasi, controller, validasi form, flash toast, export data, dan realtime Echo **tetap berjalan seperti biasa**.
3. **Single URL & Transparent Routing**: Pengguna mengakses URL yang sama (`/dashboard`, `/pengiriman`, dsb.). Sistem secara cerdas menyajikan file yang tepat berdasarkan perangkat pengguna.
4. **Zero Dead DOM**: HP Android hanya menerima kode Mobile murni; Laptop/PC hanya menerima kode Desktop murni.

---

## 2. Struktur Direktori Target

Struktur folder di `resources/js/Pages` akan ditata menjadi terpisah:

```text
resources/js/
├── Components/
│   ├── Common/               # Komponen bersama (TextInput, InputError, Modal, Button)
│   ├── Desktop/              # Komponen khusus desktop (Sidebar lebar, table desktop)
│   └── Mobile/               # Komponen khusus mobile (BottomNav, Drawer, HeroCard)
│
├── Layouts/
│   ├── AppLayoutDesktop.vue  # Layout khusus desktop (Sidebar kiri + Navbar atas)
│   └── AppLayoutMobile.vue   # Layout khusus mobile (Navbar ringkas + Bottom Navigation)
│
└── Pages/
    ├── Desktop/              # Berisi 100% kode tampilan desktop (hidden md:block)
    │   ├── Dashboard/Index.vue
    │   ├── Pengiriman/
    │   │   ├── Index.vue
    │   │   ├── Create.vue
    │   │   └── Show.vue
    │   ├── Laporan/Index.vue
    │   ├── Tarif/Index.vue
    │   ├── Tracking/
    │   │   ├── Public.vue
    │   │   └── Search.vue
    │   ├── Auth/Login.vue
    │   └── ...
    │
    └── Mobile/               # Berisi 100% kode tampilan mobile (md:hidden)
        ├── Dashboard/Index.vue
        ├── Pengiriman/
        │   ├── Index.vue
        │   ├── Create.vue
        │   └── Show.vue
        ├── Laporan/Index.vue
        ├── Tarif/Index.vue
        ├── Tracking/
        │   ├── Public.vue
        │   └── Search.vue
        ├── Auth/Login.vue
        └── ...
```

---

## 3. Fase 1: Fondasi Backend (Device Resolver Otomatis)

Agar tidak perlu mengubah puluhan method di Controller Laravel, kita menggunakan mekanisme **Dynamic Inertia Page Resolver**.

### Langkah 1.1: Membuat Helper / Middleware Deteksi Perangkat
Membuat file deteksi sederhana di Laravel untuk membaca *User-Agent*:
- Deteksi apakah request berasal dari HP (`Android`, `iPhone`, `Mobile`).
- Deteksi disimpan ke dalam request context atau session.

### Langkah 1.2: Konfigurasi Inertia Resolver di `HandleInertiaRequests.php` atau `AppServiceProvider`
Gunakan hook resolver pada Inertia:
```php
// Konsep Resolver Otomatis:
// Jika Controller memanggil: Inertia::render('Dashboard/Index', $data)
// Sistem mengecek:
// - Jika perangkat adalah Mobile dan file 'Mobile/Dashboard/Index.vue' ada -> Muat 'Mobile/Dashboard/Index'
// - Jika perangkat Desktop dan file 'Desktop/Dashboard/Index.vue' ada -> Muat 'Desktop/Dashboard/Index'
// - Jika belum dipisah -> Fallback ke 'Dashboard/Index' yang lama.
```
> **Keuntungan:** Proses pemisahan bisa dilakukan bertahap per halaman tanpa pernah membuat aplikasi error (*graceful fallback*).

---

## 4. Fase 2: Pemisahan Layout (Mengeliminasi Listener Resize)

Saat ini di `resources/js/Layouts/AppLayout.vue`, terdapat listener `window.addEventListener('resize')` dan logika pengecekan lebar layar `window.innerWidth < 768`. Di HP Android, ini memicu overhead memori.

### Langkah 2.1: Buat `AppLayoutDesktop.vue`
1. Ambil struktur layout desktop dari `AppLayout.vue`.
2. Sertakan `<Sidebar />` dan spacer navigasi desktop.
3. Hapus seluruh impor dan komponen `<BottomNav />` serta drawer mobile.

### Langkah 2.2: Buat `AppLayoutMobile.vue`
1. Ambil struktur layout mobile dari `AppLayout.vue`.
2. Sertakan `<BottomNav />` dan drawer mobile.
3. Hapus seluruh elemen sidebar desktop dan spacer-nya.
4. Hapus seluruh event listener `resize` karena di HP ukurannya sudah pasti mobile.

---

## 5. Fase 3: Prosedur Standar (SOP) Pemisahan Setiap Halaman

Setiap halaman yang memiliki dua blok (`hidden md:block` dan `md:hidden`) dipisah dengan urutan rutin berikut:

### Rutinitas Pemisahan Halaman (Contoh: `Dashboard/Index.vue`):

1. **Membuat File Desktop (`Pages/Desktop/Dashboard/Index.vue`)**:
   - Salin seluruh blok `<script setup>` yang berhubungan dengan data & chart desktop.
   - Set layout: `defineOptions({ layout: AppLayoutDesktop });`.
   - Di bagian `<template>`, ambil isi yang berada di dalam pembungkus `<div class="hidden md:block ...">`.
   - Hapus class pembungkus `hidden md:block` (gunakan div biasa atau `<div class="space-y-6">`).
   - Buang seluruh kode tampilan mobile (seperti Hero KPI mobile, decorative canvas wave mobile, quick action mobile).

2. **Membuat File Mobile (`Pages/Mobile/Dashboard/Index.vue`)**:
   - Salin seluruh blok `<script setup>` yang dibutuhkan oleh mobile.
   - Set layout: `defineOptions({ layout: AppLayoutMobile });`.
   - Di bagian `<template>`, ambil isi yang berada di dalam pembungkus `<div class="md:hidden ...">`.
   - Hapus class pembungkus `md:hidden`.
   - Buang seluruh kode desktop (seperti tabel 6 kolom desktop, sidebar kontrol desktop).

---

## 6. Fase 4: Checklist Seluruh Halaman yang Harus Dipisah

Berikut daftar seluruh halaman yang diproses secara berurutan:

- [x] **1. Dashboard (`Dashboard/Index.vue`)**
  - Pisahkan Desktop (Tabel 10 baris, 4 kartu KPI, 4 grafik ApexCharts).
  - Pisahkan Mobile (Hero KPI gradien, search bar sticky, 4-grid launcher, timeline aktivitas, bottom navigation).
- [x] **2. Manajemen Pengiriman (`Pengiriman/Index.vue`)**
  - Desktop: Tabel lebar dengan filter kompleks, dropdown aksi, dan paginasi desktop.
  - Mobile: Card-list pengiriman vertikal, swipeable tabs filter, FAB button tambah pengiriman.
- [x] **3. Input Pengiriman Baru (`Pengiriman/Create.vue`)**
  - Desktop: Multi-column form layout dengan preview tarif di sisi kanan.
  - Mobile: Single-column step-by-step form dengan tombol simpan sticky di bawah.
- [x] **4. Detail Pengiriman (`Pengiriman/Show.vue`)**
  - Desktop: Grid 2 kolom dengan peta Leaflet besar dan timeline horizontal.
  - Mobile: Timeline tracking vertikal dengan status badge ringkas dan tombol bagikan/cetak resi mobile.
- [x] **5. Laporan & Rekapitulasi (`Laporan/Index.vue`)**
  - Desktop: Export Excel/PDF panel, filter tanggal rentang, tabel rekapitulasi lengkap.
  - Mobile: Ringkasan ringkas bulanan/mingguan, tombol download cepat via modal sheet.
- [x] **6. Cek Tarif (`Tarif/Index.vue`)**
  - Desktop: Kalkulator tarif berdampingan dengan tabel perbandingan ongkir.
  - Mobile: Input asal & tujuan vertikal dengan kartu rekomendasi ongkir yang mudah di-tap.
- [x] **7. Pelacakan Resi Publik (`Tracking/Public.vue` & `Tracking/Search.vue`)**
  - Desktop: Form pencarian resi tengah dengan banner branding.
  - Mobile: Search box ringkas dengan riwayat pencarian terakhir.
- [x] **8. Autentikasi (`Auth/Login.vue`)**
  - Desktop: Split-screen (kiri ilustrasi brand, kanan form login).
  - Mobile: Card login terpusat ramah keyboard virtual HP.
- [x] **9. Pengaturan & Profil (`Profile/Edit.vue` & `Settings/Index.vue`)**
  - Desktop: Tab menu samping dengan panel pengaturan lebar.
  - Mobile: Tab bar horizontal scrollable atau accordion menu.

---

## 7. Fase 5: Optimasi Performa Khusus Mobile (Menghilangkan Lag & Patah-Patah)

Setelah dipisah ke `Pages/Mobile/`, lakukan optimasi teknis berikut pada file mobile **tanpa merubah tampilan visual**:

1. **Optimasi Background Canvas & Gradien**:
   - Ganti SVG filter `feTurbulence` dan `fractalNoise` yang berat dengan CSS radial-gradient murni. Secara visual gradien terlihat persis sama, tetapi konsumsi GPU di HP turun drastis.
   - Turunkan intensitas `filter: blur(100px)` menjadi `filter: blur(40px)` atau gunakan elemen semitransparan bertumpuk (*layering*).
2. **Isolasi ApexCharts di Mobile**:
   - Pastikan grafik ApexCharts di mobile hanya dimuat secara *asynchronous* (`defineAsyncComponent`) ketika komponen grafik terlihat di layar pengguna (*Intersection Observer*).
   - Di halaman mobile yang tidak memerlukan grafik (misal: `Pengiriman/Index.vue`, `Create.vue`), pastikan script ApexCharts sama sekali tidak diimpor.
3. **Hardware Acceleration untuk Scrolling**:
   - Tambahkan utility `transform-gpu` atau `will-change: transform` pada container utama mobile agar transisi dan scrolling diakselerasi langsung oleh chip grafis HP Android.

---

## 8. Fase 6: Verifikasi & Tolok Ukur Keberhasilan

Setelah pemisahan selesai, lakukan pengujian berikut:

1. **Uji Fungsionalitas**:
   - Test login, create pengiriman, edit profil, cetak resi, cek tarif, dan pelacakan resi.
   - Pastikan seluruh data input dan respon backend tetap sinkron 100%.
2. **Uji Tampilan (Visual Regression Test)**:
   - Buka versi Desktop di Chrome PC: pastikan tampilan tidak bergeser sama sekali.
   - Buka versi Mobile di Chrome Android / DevTools Device Toolbar (resolusi 360px - 414px): pastikan seluruh elemen mobile tampil persis seperti sebelumnya.
3. **Tolok Ukur Performa (Benchmark HP Android)**:
   - **DOM Nodes**: Jumlah elemen DOM aktif di HP berkurang minimal 40–50%.
   - **Ukuran JavaScript Awal**: Berkurang signifikan karena kode desktop tidak lagi ikut terunduh (contoh: bundle Create turun dari ~55 KB ke ~29 KB).
   - **Scroll FPS**: Scrolling di dashboard dan daftar pengiriman mencapai 60 FPS mulus tanpa patah-patah.

---

### Status Dokumen:
- **Status**: SELESAI (100% Implemented & Verified).
- **Lokasi Project**: `c:\xampp\htdocs\pengiriman`
- **Sifat Dokumen**: SOP Eksekusi Baku (Rutin dari awal hingga selesai).
