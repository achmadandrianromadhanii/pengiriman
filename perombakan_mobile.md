# FINAL REVISION
# MOBILE WEB ONLY
# HALAMAN MENU → JELAJAH → KEAMANAN
# FOKUS HANYA TOGGLE SWITCH DAN BUG FIX

================================================================

PERINGATAN PALING PENTING

JANGAN MERUBAH:

- Layout
- Typography
- Card
- Margin
- Padding
- Warna halaman
- Icon
- Struktur halaman
- Animasi lain
- Bottom Sheet Biometrik
- Halaman lain

Semua UI yang sudah bagus WAJIB DIPERTAHANKAN.

================================================================

TARGET

Fokus hanya memperbaiki Toggle Switch.

================================================================

TOGGLE SWITCH

HAPUS implementasi Toggle Switch custom lama apabila memang menjadi penyebab bug.

Gunakan Toggle Switch original dari library.

Prioritas:

1. Radix Vue Switch
2. Headless UI Switch
3. PrimeVue InputSwitch

Jangan membuat Toggle Switch dengan CSS custom.

================================================================

SPESIFIKASI

Track

- width 42px
- height 24px

Thumb

- 18px

Padding

- 3px

Radius

- full

Animasi

- transition 200ms ease
- translateX
- tanpa bounce
- tanpa rotate
- tanpa ripple

Warna OFF

#E4EAF3

Warna ON

#2F5DFF

Thumb putih.

Shadow thumb ringan.

================================================================

STATE

Status Toggle wajib berasal dari backend.

Tidak boleh menggunakan state visual.

Harus sinkron dengan database.

Refresh halaman:

Status tetap.

Pindah halaman:

Status tetap.

Logout:

Status tetap.

================================================================

BUG

Perbaiki seluruh bug Toggle.

Tidak boleh ada:

- Flicker
- Delay
- Double Click
- Ghost Click
- UI mismatch
- Hydration warning
- Vue warning
- Memory leak

================================================================

RESPONSIVE

Pastikan proporsional pada:

320

360

375

390

414

430

480

================================================================

KODE

Hapus CSS lama yang menyebabkan conflict.

Pastikan tidak ada CSS duplicate.

Pastikan component bersih.

================================================================

OUTPUT

Berikan laporan:

✔ Penyebab bug

✔ File yang diubah

✔ Kenapa diubah

✔ Bug yang diperbaiki

================================================================

HASIL AKHIR

✔ Toggle Switch original library

✔ Proporsional

✔ Premium

✔ Mirip iOS / Material 3

✔ Sinkron backend

✔ Sinkron database

✔ Tidak mengubah UI lain

✔ Tidak mengubah halaman lain

✔ Tidak mengubah desain yang sudah bagus

✔ Kode bersih

✔ Tidak ada warning

✔ Tidak ada error

✔ Stabil di Local maupun Vercel

Wireframe
────────────────────────

🔔 Notifikasi Login

Beritahu saat ada login baru

                      ╭──────╮
                      │    ● │
                      ╰──────╯

────────────────────────

⏱ Auto Logout

Logout otomatis saat idle

                      ╭──────╮
                      │ ●    │
                      ╰──────╯

────────────────────────

🪪 Biometrik

Sidik jari / Face ID

                      ╭──────╮
                      │    ● │
                      ╰──────╯