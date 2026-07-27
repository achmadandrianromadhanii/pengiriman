# FINAL REVISION — MOBILE MENU (CACHE • STORAGE • ABOUT)

ROLE

Bertindak sebagai:

- Senior Mobile UI/UX Designer
- Senior Laravel Engineer
- Senior Vue Engineer
- Senior Mobile Performance Engineer
- Senior PostgreSQL Engineer
- Senior PWA Engineer

================================================================================
TUJUAN
================================================================================

Fokus HANYA pada halaman:

✓ Cache
✓ Penyimpanan
✓ Tentang Aplikasi

JANGAN mengubah halaman lain.

Dashboard tetap.

Login tetap.

Tracking tetap.

Pengiriman tetap.

Laporan tetap.

Business Logic tetap.

Database Schema tetap.

API tetap.

================================================================================
TARGET
================================================================================

Semua data yang tampil HARUS berasal dari data asli sistem.

Bukan dummy.

Bukan angka visual.

Bukan hardcode.

Seluruh data harus sinkron dengan:

✓ Laravel Backend

✓ PostgreSQL

✓ Neon Database

✓ Browser Storage

✓ PWA Cache

✓ Service Worker

================================================================================
1. HALAMAN CACHE
================================================================================

PERTAHANKAN layout sekarang.

Tetapi upgrade menjadi halaman diagnosa cache.

Cache yang ditampilkan HARUS asli.

Ambil dari:

Browser Cache API

Service Worker Cache

LocalStorage

SessionStorage

IndexedDB

Image Cache

Temporary File

PWA Cache

Application Cache

Total Cache

================================================================================
RINCIAN CACHE
================================================================================

Storage Cache

Browser Cache

Image Cache

API Cache

Temporary Cache

Session Storage

Local Storage

IndexedDB

PWA Offline Cache

Total

Semua ukuran:

KB

MB

GB

secara otomatis.

================================================================================
TOTAL CACHE
================================================================================

Hitung otomatis.

Misal:

52.8 MB

Jika cache bertambah maka otomatis naik.

Jika dibersihkan maka otomatis turun.

Realtime.

================================================================================
BERSIHKAN CACHE
================================================================================

Saat tombol ditekan:

Popup konfirmasi premium.

Jika Ya

↓

Loading Wave

↓

Progress

↓

Membersihkan:

Browser Cache

Image Cache

PWA Cache

IndexedDB

Temporary Cache

↓

Cache selesai dibersihkan.

Tanpa reload.

================================================================================
ANIMASI
================================================================================

PERTAHANKAN.

Tidak perlu ditambah.

Tetap ringan.

60 FPS.

================================================================================
2. HALAMAN PENYIMPANAN
================================================================================

Ini jangan memakai data visual.

HARUS asli.

Ambil dari PostgreSQL.

Neon Database.

================================================================================
HEADER
================================================================================

Database Engine

Neon PostgreSQL

Ukuran Database

58.2 MB

Ukuran dihitung asli.

================================================================================
STATISTIK
================================================================================

Jumlah Pengiriman

Jumlah Pelanggan

Jumlah Riwayat

Jumlah Lampiran

Jumlah Admin

Jumlah User

Ukuran Database

Ukuran File Upload

Total Record

Semua berasal dari query database.

================================================================================
AKSI
================================================================================

Sinkronkan Database

↓

Melakukan sinkronisasi.

Refresh data.

Tidak reload.

Export Database

↓

Download backup SQL / PostgreSQL sesuai hak akses.

Refresh Statistik

↓

Mengambil ulang ukuran database.

================================================================================
PENTING
================================================================================

JANGAN tampilkan tombol:

"Hapus Semua Database"

di Mobile.

Jika memang dibutuhkan fitur tersebut, hanya tersedia pada panel Super Admin desktop dengan autentikasi ulang.

================================================================================
3. HALAMAN TENTANG APLIKASI
================================================================================

Upgrade menjadi halaman informasi sistem.

================================================================================
LOGO
================================================================================

Gunakan logo asli aplikasi.

Bukan placeholder.

Resolusi tinggi.

HD.

Jernih.

================================================================================
INFORMASI SISTEM
================================================================================

Semua data HARUS otomatis.

Tidak hardcode.

Ambil otomatis.

Contoh:

Versi Aplikasi

Build Number

Commit Hash (jika tersedia)

Laravel Version

Vue Version

Vite Version

PHP Version

PostgreSQL Version

Neon Status

Environment

Storage Driver

Timezone

Build Date

Last Update

Semua otomatis.

Jika deploy baru

↓

Versi berubah otomatis.

Build berubah otomatis.

Tanggal berubah otomatis.

================================================================================
STATUS SERVER
================================================================================

Tambahkan card baru.

Status Backend

Online

Database

Connected

API

Healthy

Storage

Normal

Realtime.

================================================================================
LUCIDE ICON
================================================================================

Gunakan icon Lucide.

Jangan campur icon lain.

================================================================================
PENGEMBANG
================================================================================

PERTAHANKAN.

Tidak perlu diubah.

================================================================================
DESAIN
================================================================================

Pertahankan layout sekarang.

Tetapi upgrade:

Spacing lebih presisi.

Radius konsisten.

Icon 20 px.

Title 24 px.

Subtitle 13 px.

Body 14 px.

Line Height konsisten.

================================================================================
PERFORMA
================================================================================

Tidak boleh menambah library berat.

Tidak boleh lag.

Tidak boleh freeze.

Tidak boleh blocking render.

Semua asynchronous.

================================================================================
HASIL AKHIR
================================================================================

Cache menggunakan data asli browser dan PWA.

Penyimpanan menggunakan data asli PostgreSQL / Neon.

Tentang Aplikasi menggunakan metadata sistem secara otomatis.

Semua realtime.

Semua sinkron.

Semua ringan.

Semua terasa seperti aplikasi enterprise profesional.