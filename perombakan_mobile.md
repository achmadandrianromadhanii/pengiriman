# FINAL REVISION — MOBILE WEB SETTINGS (CACHE & SECURITY)

## ROLE

Bertindak sebagai:

- Senior Laravel Engineer
- Senior PostgreSQL Engineer
- Senior PWA Engineer
- Senior Mobile Web Engineer
- Senior Security Engineer
- Senior UI/UX Engineer

================================================================================
TUJUAN
================================================================================

Fokus HANYA pada halaman:

- Cache
- Keamanan

JANGAN mengubah halaman lain.

JANGAN mengubah:

- Dashboard
- Login
- Laporan
- Tracking
- Tarif
- Pengiriman
- UI utama
- Animasi yang sudah ada

================================================================================
TARGET
================================================================================

Mengubah halaman Cache dan Keamanan dari tampilan visual menjadi modul enterprise yang menggunakan DATA ASLI secara realtime.

================================================================================
1. CACHE
================================================================================

Seluruh data cache HARUS berasal dari data nyata browser/PWA.

Hitung ukuran secara realtime menggunakan sumber yang memang tersedia, seperti:

- Cache Storage API (Service Worker cache)
- LocalStorage
- SessionStorage
- IndexedDB
- Browser cache yang dapat diakses aplikasi
- Temporary offline assets
- Manifest/PWA cache
- Cache gambar yang dikelola aplikasi

Jangan menggunakan angka statis atau dummy.

Tampilkan total cache yang merupakan hasil penjumlahan semua sumber tersebut.

================================================================================
2. PEMBERSIHAN CACHE
================================================================================

Saat tombol "Bersihkan Cache" ditekan:

- Tampilkan dialog konfirmasi.
- Jalankan proses pembersihan cache nyata.
- Bersihkan hanya cache yang aman untuk dihapus:
  - Cache Storage
  - IndexedDB aplikasi
  - SessionStorage
  - LocalStorage non-esensial
  - Temporary image cache
  - Offline assets

JANGAN menghapus:
- Session login
- Token autentikasi
- Preferensi pengguna
- Tema aplikasi

Selama proses berjalan:

- Tampilkan popup loading.
- Progress bar bergerak bertahap.
- Nilai total cache berkurang secara realtime hingga selesai.
- Setelah selesai tampilkan notifikasi sukses dengan jumlah ruang yang berhasil dibersihkan.

================================================================================
3. KEAMANAN
================================================================================

Seluruh informasi keamanan HARUS berasal dari data nyata sistem.

Status Password:
- Ambil dari metadata pengguna (misalnya `password_changed_at`).
- Jika belum pernah diubah, tampilkan "Belum pernah diubah".

Riwayat Login:
- Ambil dari tabel riwayat login.
- Tampilkan:
  - Tanggal
  - Jam
  - Browser
  - Sistem operasi
  - IP
  - Lokasi (jika tersedia)

Perangkat Saat Ini:
- Deteksi otomatis:
  - Nama perangkat
  - Browser
  - Sistem operasi
  - Jenis perangkat (Desktop / Android / iPhone)
  - Status ONLINE

================================================================================
4. MANAJEMEN PERANGKAT
================================================================================

Tambahkan halaman "Perangkat Terhubung".

Daftar seluruh sesi aktif dengan informasi:
- Nama perangkat
- Browser
- Sistem operasi
- Lokasi
- Waktu login terakhir
- Status aktif

Sediakan aksi:
- Logout perangkat tertentu
- Logout semua perangkat (dengan konfirmasi)

================================================================================
5. PENGATURAN KEAMANAN
================================================================================

Tambahkan opsi:
- Notifikasi login baru (ON/OFF)
- Logout otomatis saat idle (ON/OFF)
- Biometric Ready (ON/OFF jika didukung PWA)

================================================================================
6. SINKRONISASI
================================================================================

Semua data HARUS sinkron secara realtime menggunakan backend aplikasi (Laravel + PostgreSQL + Broadcast/Pusher jika sudah tersedia).

Perubahan login, logout, atau metadata keamanan harus langsung tercermin pada halaman tanpa refresh.

================================================================================
7. UI/UX
================================================================================

Pertahankan desain yang sudah ada.

Jangan mengubah layout utama.

Gunakan ikon Lucide secara konsisten.

Tambahkan hanya animasi ringan pada perubahan data (angka cache, status sinkron, progres pembersihan).

================================================================================
HASIL AKHIR
================================================================================

✔ Seluruh data berasal dari sumber nyata.
✔ Tidak ada angka dummy.
✔ Cache benar-benar dibersihkan secara aman.
✔ Informasi keamanan sinkron dengan data pengguna dan sesi login.
✔ Semua fitur bekerja realtime.
✔ Tampilan tetap ringan, modern, dan konsisten dengan UI aplikasi.

Wireframe Keamanan
┌──────────────────────────┐

STATUS PASSWORD

Aktif

24 Juli 2026

──────────────

LOGIN TERAKHIR

Hari ini

14.22 WIB

Chrome

Windows 11

──────────────

PERANGKAT

Samsung A55

Android 15

Chrome

ONLINE

──────────────

Toggle

Login Notification

ON

Auto Logout

OFF

Biometric

ON

──────────────

Logout Semua Perangkat

└──────────────────────────┘

Wireframe Cache
┌──────────────────────────┐

TOTAL CACHE

108.25 MB

██████████████

Browser

48 MB

███████

Image

32 MB

█████

IndexedDB

20 MB

███

Storage

8 MB

──────────────

[ Bersihkan Cache ]

↓

Popup

Membersihkan...

72%

██████████░░

↓

0 KB

✓ Berhasil

└──────────────────────────┘