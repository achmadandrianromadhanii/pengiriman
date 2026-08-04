# FINAL STABILIZATION
# MOBILE WEB ONLY
# HALAMAN MENU → JELAJAH → KEAMANAN

====================================================================

ROLE

Kamu adalah:

• Senior Laravel Engineer
• Senior Vue 3 Engineer
• Senior Mobile UI Engineer
• Senior PWA Engineer
• Senior Performance Engineer
• Senior QA Engineer

====================================================================

TUJUAN

Fokus HANYA memperbaiki halaman:

Mobile Web
Menu
→ Jelajah
→ Keamanan

JANGAN menyentuh halaman lain.

JANGAN mengubah UI lain.

JANGAN mengubah layout yang sudah baik.

JANGAN mengubah warna.

JANGAN mengubah card.

JANGAN mengubah spacing.

JANGAN mengubah animasi popup biometrik.

JANGAN mengubah animasi login.

====================================================================

PRIORITAS PEKERJAAN

1.
STABILKAN seluruh halaman keamanan.

Hilangkan seluruh bug.

Hilangkan seluruh error.

Hilangkan seluruh warning yang berasal dari source code aplikasi.

Pastikan kode bersih.

====================================================================

2.
TOGGLE SWITCH

Implementasi Toggle Switch saat ini dianggap gagal.

Jangan lagi menggunakan implementasi custom lama.

Gunakan Toggle Switch original dari library.

Prioritas:

1. Radix Vue Switch
2. Headless UI Switch
3. PrimeVue InputSwitch

Tidak boleh membuat Toggle menggunakan CSS custom.

====================================================================

SPESIFIKASI SWITCH

Track

42 x 24 px

Thumb

18 x 18 px

Radius

Full

Transition

200ms ease

OFF

#E5EBF4

ON

#2F5DFF

Thumb putih

Shadow ringan

Tidak boleh:

- Oval aneh
- Thumb lonjong
- Thumb tidak center
- Track terlalu besar
- Track terlalu kecil

Target tampilannya mendekati:

iOS Settings

Material 3

Google Wallet

====================================================================

STATE SWITCH

Semua Toggle wajib sinkron.

Status berasal dari database.

Bukan state visual.

Bukan state lokal.

Alur:

Tap

↓

API

↓

Laravel

↓

Database

↓

Broadcast

↓

Pinia

↓

UI

Refresh

↓

Tetap sama

Pindah halaman

↓

Tetap sama

Logout

↓

Tetap sama

====================================================================

SETTING YANG HARUS DISINKRONKAN

- Notifikasi Login

- Auto Logout

- Biometrik

Semua harus realtime.

====================================================================

3.
PERBAIKI BIOMETRIK

Saat ini terdapat bug.

Alur sekarang:

Aktifkan biometrik

↓

Konfirmasi

↓

404

↓

Login berhasil menggunakan biometrik

↓

Tetapi Toggle Biometrik OFF

Ini SALAH.

Harus diperbaiki.

====================================================================

ALUR BIOMETRIK BARU

Aktifkan biometrik

↓

Bottom Sheet

↓

Pilih

- Sidik Jari

atau

- Face ID

↓

Validasi WebAuthn / Passkey

↓

Registrasi berhasil

↓

Simpan credential

↓

Simpan status ke database

↓

Update session

↓

Broadcast

↓

Pinia

↓

Toggle langsung ON

↓

Refresh

↓

Tetap ON

↓

Pindah halaman

↓

Tetap ON

Tidak boleh ada 404.

====================================================================

Jika registrasi gagal

↓

Tidak boleh redirect.

Tidak boleh blank.

Tidak boleh error.

Harus muncul dialog:

"Pendaftaran biometrik gagal."

"Tidak ada perubahan pada akun."

====================================================================

Jika device tidak mendukung

↓

Dialog:

"Perangkat ini belum mendukung autentikasi biometrik."

====================================================================

Jika biometrik berhasil

↓

Dialog:

"Biometrik berhasil diaktifkan."

====================================================================

4.
LOGIN BIOMETRIK

Saat halaman login dibuka

Jika biometrik aktif

↓

Munculkan Bottom Sheet

↓

Pilih:

Sidik Jari

Face ID

Login Email

Jika memilih Login Email

↓

Bottom Sheet menutup dengan animasi

Tidak redirect.

====================================================================

5.
PERANGKAT

Pastikan nama perangkat asli.

Contoh:

iPhone 15 Pro

Samsung Galaxy S24

OPPO Find X8

Google Pixel 9

Windows 11

Windows 10

macOS

ChromeOS

Jangan hanya Desktop.

Jangan hanya Mobile.

====================================================================

6.
RIWAYAT LOGIN

Sinkron dengan database.

Data asli.

Realtime.

Tidak dummy.

====================================================================

7.
LOGOUT SEMUA PERANGKAT

Pastikan benar-benar:

Menghapus session aktif lain.

Menghapus remember token.

Menghapus passkey aktif bila diperlukan.

Broadcast logout.

Realtime.

====================================================================

8.
BERSIHKAN KODE

Hilangkan:

- Duplicate watcher

- Duplicate API

- Duplicate state

- Dead code

- CSS conflict

- Memory leak

- Console warning

- Vue warning

- Promise rejection

====================================================================

9.
PWA

Pastikan fitur tetap normal:

Browser

Android Chrome

Safari iOS

Installed PWA

Vercel

Localhost

====================================================================

10.
JANGAN MERUBAH UI

Halaman keamanan saat ini sudah baik.

Yang boleh berubah HANYA:

Toggle Switch

Bug

Sinkronisasi data

Flow biometrik

Selain itu WAJIB IDENTIK.

====================================================================

OUTPUT

Berikan laporan:

✔ Bug ditemukan

✔ Penyebab

✔ File yang diubah

✔ Kenapa diubah

✔ Bug berhasil diperbaiki

✔ Pengujian yang dilakukan

====================================================================

HASIL AKHIR

✔ Tidak ada 404

✔ Toggle original library

✔ Toggle sinkron database

✔ Biometrik sinkron database

✔ Biometrik sinkron session

✔ Biometrik sinkron login

✔ UI tetap sama

✔ Tidak mengubah halaman lain

✔ Tidak ada bug

✔ Tidak ada warning aplikasi

✔ Tidak ada memory leak

✔ Tidak ada duplicate state

✔ Kode bersih

✔ Stabil di Localhost

✔ Stabil di Vercel

✔ Stabil sebagai PWA Mobile Web

Wireframe target (tetap sama, hanya Toggle yang diperbaik
┌────────────────────────────────────┐
│ PENGATURAN                         │
│                                    │
│ 🔔 Notifikasi Login          ╭────╮│
│ Beritahu saat ada login baru │ ○  ││
│                              ╰────╯│
├────────────────────────────────────┤
│ ⏱ Auto Logout               ╭────╮│
│ Logout otomatis saat idle   │ ●  ││
│                             ╰────╯│
├────────────────────────────────────┤
│ 🟣 Biometrik                ╭────╮│
│ Login dengan sidik jari     │ ○  ││
│ / Face ID                   ╰────╯│
└────────────────────────────────────┘

      ┌──────────────────────────────┐
      │ ⏻ Logout Semua Perangkat     │
      └──────────────────────────────┘