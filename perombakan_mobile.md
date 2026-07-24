# FINAL REVISION — SISTEM RIWAYAT EXPORT PDF & ARSIP (MOBILE REPORT)

ROLE:
Bertindak sebagai Senior UI/UX Designer dan Senior Full Stack Engineer.

================================================================================
TUJUAN
================================================================================

Lakukan penyempurnaan sistem Riwayat Export PDF dan Arsip Export pada halaman Laporan Mobile.

Fokus HANYA pada fitur Riwayat Export dan Arsip.

DILARANG mengubah:

- Dashboard
- Login
- Tracking
- Pengiriman
- Tarif
- Menu lain
- Database utama
- Business Logic selain fitur arsip

================================================================================
LOGIKA BARU
================================================================================

Riwayat Export hanya menyimpan maksimal 5 file PDF terbaru.

Ketika pengguna melakukan export PDF ke-6:

JANGAN menghapus satu per satu.

Tetapi:

1. Ambil seluruh 5 riwayat yang ada.
2. Kelompokkan menjadi SATU Batch Arsip.
3. Batch tersebut dipindahkan ke halaman Arsip.
4. Riwayat Export dikosongkan.
5. PDF hasil export terbaru menjadi riwayat pertama pada batch baru.

Contoh:

Recent

PDF1
PDF2
PDF3
PDF4
PDF5

↓

Export lagi

↓

Arsip

Batch 24 Juli 2026

PDF1
PDF2
PDF3
PDF4
PDF5

↓

Recent

PDF6

================================================================================
HALAMAN RIWAYAT EXPORT
================================================================================

Jika kosong tampilkan Empty State profesional:

🗂

Belum ada riwayat export terbaru.

Riwayat sebelumnya telah dipindahkan ke Arsip.

Jangan menampilkan card kosong.

================================================================================
HALAMAN ARSIP
================================================================================

Popup Arsip menggunakan Bottom Sheet.

Scroll hanya di dalam popup.

Website utama tidak ikut scroll.

================================================================================
STRUKTUR ARSIP
================================================================================

Setiap Batch terdiri dari:

──────────────────────────

📅 Hari, Tanggal Bulan Tahun

Contoh:

Jumat, 24 Juli 2026

Batch #01

──────────────────────────

Lalu tampilkan tepat 5 riwayat export yang dipindahkan.

Setelah selesai beri divider tegas.

══════════════════════════

Kemudian Batch berikutnya.

================================================================================
DIVIDER
================================================================================

Gunakan divider horizontal yang jelas antar batch.

Jangan menggunakan card besar sebagai pemisah.

================================================================================
DATA WAKTU
================================================================================

Hari

Tanggal

Bulan

Tahun

Jam

Semua wajib berasal dari data realtime server/Pusher.

Tidak boleh menggunakan waktu lokal browser.

================================================================================
ANIMASI
================================================================================

Saat proses pemindahan batch:

Tampilkan toast singkat:

✓

5 Riwayat berhasil dipindahkan ke Arsip.

Durasi 2 detik.

================================================================================
HASIL AKHIR
================================================================================

✔ Riwayat Export hanya berisi maksimal 5 data aktif.
✔ Saat mencapai batch penuh dan ada export berikutnya, 5 data sebelumnya dipindahkan sekaligus ke Arsip.
✔ Arsip dikelompokkan berdasarkan Batch dan tanggal.
✔ Setiap Batch memiliki judul tanggal yang jelas.
✔ Riwayat Export menjadi kosong setelah dipindahkan, lalu mulai mengumpulkan batch berikutnya.
✔ Popup Arsip memiliki scroll internal sendiri.
✔ UI tetap ringan, rapi, dan konsisten dengan desain mobile aplikasi.

┌────────────────────────────┐

 Arsip Riwayat            ✕

──────────────────────────────

📅 Jumat, 24 Juli 2026

Batch #01

──────────────────────────────

PDF

PDF

PDF

PDF

PDF

══════════════════════════════

📅 Sabtu, 26 Juli 2026

Batch #02

──────────────────────────────

PDF

PDF

PDF

PDF

PDF

└────────────────────────────┘

Riwayat Export

────────────────────

📄 Laporan Juli

📄 Laporan Juli

📄 Laporan Juli

🗂

Belum ada riwayat terbaru.

Semua riwayat telah dipindahkan
ke Arsip.

Export

↓

Masuk Recent History

↓

Jumlah = 5

↓

Export berikutnya

↓

5 data dipindahkan

↓

Membuat Batch Baru

↓

Recent kosong

↓

Export terbaru masuk Recent