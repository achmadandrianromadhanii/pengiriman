# ============================================================================
# FINAL REPORT CENTER PRODUCTION POLISH
# MOBILE REPORT ONLY
# ============================================================================

Lakukan penyempurnaan akhir terhadap halaman Report Center Mobile.

JANGAN mengubah Dashboard.

JANGAN mengubah halaman lain.

JANGAN mengubah Backend, Database, API, maupun Business Logic.

Fokus hanya pada Report Center Mobile.

------------------------------------------------------------------------------

## 1. Operational Status Bar (Ganti Area yang Dilingkari)

Hapus informasi user yang tampil dua kali.

Jangan tampilkan kembali nama user.

Ganti area tersebut menjadi Operational Status Bar.

Isi:

- Status Sinkronisasi
- Periode Aktif
- Waktu Update Terakhir
- Status Sistem

Contoh:

🟢 Sinkronisasi Aktif

Periode:
Bulan Ini

Terakhir diperbarui:

23 Jul 2026 • 20:31 WIB

Data harus realtime mengikuti waktu server.

Tidak menggunakan waktu browser.

------------------------------------------------------------------------------

## 2. Background

Perbarui Decorative Canvas.

Gunakan:

Layer 1

Gradient

Layer 2

Glow

Layer 3

Contour Pattern

Layer 4

Wave Pattern

Layer 5

Noise

Pattern dibuat lebih halus.

Opacity:

Contour 4%

Wave 3%

Noise 1%

Background memenuhi 100vw.

Tidak ada warna putih di sisi kiri dan kanan.

Bentuk berupa setengah lingkaran besar dengan transisi organik.

------------------------------------------------------------------------------

## 3. Jenis Laporan

Gunakan segmented control horizontal.

Pengiriman | Pendapatan | Operasional

Tinggi sekitar 44–48px.

Lebar menyesuaikan layar.

------------------------------------------------------------------------------

## 4. Tombol PDF

Gunakan satu tombol utama.

Ikon Lucide FileText.

Judul:

Buat Laporan PDF

Subjudul:

Dokumen resmi siap cetak A4

Tambahkan ikon panah di kanan.

------------------------------------------------------------------------------

## 5. Loading

Saat tombol ditekan:

- Blur background
- Modal kecil di tengah
- Spinner
- Progress
- Success
- Auto download

Tidak membuka tab baru.

Tetap berada di halaman yang sama.

------------------------------------------------------------------------------

## 6. Riwayat Export

Halaman utama hanya menampilkan maksimal 5 export terbaru.

Jika jumlah export lebih dari 5:

Riwayat lama otomatis dipindahkan ke arsip.

Pada halaman utama tampilkan:

"Lihat Arsip"

Saat ditekan:

Buka Bottom Sheet fullscreen.

Bukan halaman baru.

Bottom Sheet memiliki scroll internal.

Setiap item menampilkan:

- Nama file
- Tanggal
- Hari
- Bulan
- Tahun
- Jam
- Ukuran file
- Tombol Unduh Lagi

Semua data menggunakan waktu server secara realtime.

------------------------------------------------------------------------------

## 7. PDF

Rombak total isi PDF.

Format:

- Logo PT
- Nama PT
- Judul Laporan
- Periode
- Cabang
- Operator
- Tanggal Cetak
- Ringkasan Operasional
- Tabel Pengiriman
- Catatan
- Tanda Tangan Pemilik PT
- Tanda Tangan Penanggung Jawab
- Footer

Tanpa chart.

Tanpa KPI.

Tanpa card.

Tanpa dekorasi dashboard.

Gunakan layout laporan resmi perusahaan.

Format A4 Portrait.

------------------------------------------------------------------------------

## 8. Nama File

Gunakan:

Laporan-Pengiriman-YYYY-MM.pdf

Singkat.

Profesional.

------------------------------------------------------------------------------

## Acceptance Criteria

✔ Area user tidak tampil dua kali.
✔ Diganti menjadi Operational Status Bar.
✔ Background lebih halus dan premium.
✔ Loading tetap di halaman yang sama.
✔ Riwayat utama maksimal 5 item.
✔ Arsip menggunakan Bottom Sheet.
✔ PDF dibuat ulang menjadi laporan resmi.
✔ Dua kolom tanda tangan.
✔ Semua waktu mengikuti server secara realtime.
✔ Tidak ada perubahan pada halaman selain Report Center Mobile.

Wireframe Target
╔════════════════════════════════════════════╗
║ 👤 Ryan                          🔔 🌙      ║
╠════════════════════════════════════════════╣
║ 🟢 Sinkronisasi Aktif                      ║
║ Periode : Bulan Ini                        ║
║ Update : 23 Jul 2026 • 20:31 WIB           ║
╠════════════════════════════════════════════╣
║ 📄 Report Center             [ READY ]     ║
║ Laporan Operasional Perusahaan             ║
╚════════════════════════════════════════════╝

┌──────────────────────────────────────────┐
│ 📅 Bulan Ini ▼                           │
└──────────────────────────────────────────┘

┌────────────┬────────────┬────────────┐
│Pengiriman ✓│Pendapatan  │Operasional │
└────────────┴────────────┴────────────┘

┌──────────────────────────────────────────┐
│ 📄 Buat Laporan PDF                  →   │
│ Dokumen resmi siap cetak A4              │
└──────────────────────────────────────────┘

🕒 Riwayat Terbaru (maks. 5)

📄 Laporan-Pengiriman-2026-07.pdf
23 Jul 2026 • 20:31 • 1.2 MB

[Lihat Arsip]