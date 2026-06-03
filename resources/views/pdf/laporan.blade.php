<!doctype html>
<html lang="id">
<!--
  LAPORAN PDF SUPERSTART — FORMAT A4 LANDSCAPE (TCPDF)
  =====================================================
  Fungsi  : Mencetak laporan data pengiriman dalam format PDF A4 Landscape.
  Engine  : TCPDF (bukan DomPDF). Semua CSS harus kompatibel TCPDF.
  Fitur   : 1. Header dokumen (nama perusahaan + info cetak)
             2. Ringkasan KPI (Total Pengiriman, Pendapatan, Terkirim, Gagal)
             3. Kotak info periode filter
             4. Tabel data dengan 10 kolom proporsional
             5. Grand Total di footer tabel
             6. Area tanda tangan pengesahan
  Catatan : TCPDF TIDAK mendukung: position:fixed, border-radius, flexbox, grid.
            Semua layout menggunakan <table> dengan inline style sederhana.
-->
<head>
    <meta charset="utf-8">
    <title>Laporan SuperStart</title>
    <style>
        /* ── Reset & Base ────────────────────────────────────────── */
        /* Fungsi: Mengatur font dasar dan warna dokumen PDF */
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
            margin: 0;
            padding: 0;
        }

        /* ── Utilitas Umum ────────────────────────────────────────── */
        /* Fungsi: Kelas bantu untuk alignment dan styling teks */
        .muted { color: #6b7280; }
        .small { font-size: 8px; }
        .bold  { font-weight: 700; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }

        /* ── Header Dokumen ───────────────────────────────────────── */
        /* Fungsi: Bagian atas dokumen, berisi nama perusahaan dan info cetak */
        .doc-header {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .doc-header td {
            vertical-align: middle;
            padding: 0;
        }
        .brand-name {
            font-size: 18px;
            font-weight: 800;
            color: #4f46e5;
            margin: 0;
            padding: 0;
        }
        .brand-desc {
            font-size: 9px;
            color: #6b7280;
            margin: 2px 0 0 0;
        }
        .header-info {
            text-align: right;
            font-size: 9px;
            color: #6b7280;
        }
        .header-info strong {
            font-size: 11px;
            color: #1f2937;
        }

        /* ── Ringkasan KPI ────────────────────────────────────────── */
        /* Fungsi: 4 kotak metrik utama di atas tabel data */
        .kpi-table {
            width: 100%;
            margin-bottom: 14px;
        }
        .kpi-table td {
            width: 25%;
            padding: 8px 10px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
            background-color: #f9fafb;
        }
        .kpi-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .kpi-value {
            font-size: 14px;
            font-weight: 800;
            color: #1f2937;
            margin-top: 4px;
        }

        /* ── Kotak Info Filter (Periode) ──────────────────────────── */
        /* Fungsi: Menampilkan parameter filter yang digunakan saat cetak */
        .meta-table {
            width: 100%;
            margin-bottom: 14px;
            border: 1px solid #e5e7eb;
        }
        .meta-table td {
            padding: 6px 10px;
            font-size: 9px;
            vertical-align: top;
        }
        .meta-key {
            font-weight: 700;
            width: 12%;
            color: #374151;
        }
        .meta-sep {
            width: 2%;
            color: #374151;
        }
        .meta-val {
            width: 19%;
        }

        /* ── Tabel Data Utama ─────────────────────────────────────── */
        /* Fungsi: Tabel utama berisi seluruh data pengiriman yang difilter */
        /* Cara Kerja: 10 kolom dengan lebar proporsional yang dijumlahkan = 100% */
        /*             Ini mencegah kolom saling menindih pada kertas A4 Landscape */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background-color: #4f46e5;
            color: #ffffff;
            text-align: left;
            padding: 7px 5px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #4338ca;
        }
        .data-table td {
            padding: 6px 5px;
            font-size: 8.5px;
            vertical-align: top;
            /* UPDATE: Garis border solid di SETIAP sel tabel */
            /* Fungsi: Mencegah data "bocor" keluar kolom, membuat tabel tertata rapi */
            border: 1px solid #d1d5db;
        }

        /* ── Warna Status Pengiriman di PDF ────────────────────────── */
        /* Fungsi: Menjadikan sel sebagai blok warna murni untuk merepresentasikan status */
        /* Cara Kerja: Warna solid terang tanpa teks agar laporan terlihat bersih */
        .status-terkirim         { background-color: #10b981; } /* Hijau (Emerald) */
        .status-dalam-perjalanan { background-color: #3b82f6; } /* Biru */
        .status-diproses         { background-color: #f59e0b; } /* Kuning/Amber */
        .status-pending          { background-color: #9ca3af; } /* Abu-abu */
        .status-tiba-kota        { background-color: #6366f1; } /* Indigo */
        .status-sedang-diantar   { background-color: #8b5cf6; } /* Ungu (Violet) */
        .status-gagal            { background-color: #ef4444; } /* Merah */
        .status-dibatalkan       { background-color: #ef4444; } /* Merah */

        /* ── Baris Grand Total ────────────────────────────────────── */
        /* Fungsi: Menampilkan total biaya dari seluruh data */
        .grand-total td {
            border: 1px solid #4338ca;
            background-color: #eef2ff;
            font-weight: 800;
            font-size: 10px;
            padding: 8px 5px;
            color: #1f2937;
        }

        /* ── Footer Dokumen ───────────────────────────────────────── */
        /* Fungsi: Catatan kaki di bagian bawah dokumen */
        .doc-footer {
            width: 100%;
            margin-top: 12px;
            border-top: 1px solid #e5e7eb;
            padding-top: 6px;
        }
        .doc-footer td {
            font-size: 8px;
            color: #9ca3af;
            padding: 2px 0;
            vertical-align: middle;
        }

        /* ── Area Tanda Tangan ────────────────────────────────────── */
        /* Fungsi: Ruang resmi untuk tanda tangan pengesahan laporan */
        .sig-table {
            width: 100%;
            margin-top: 30px;
        }
        .sig-table td {
            width: 33%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
            font-size: 9px;
        }
        .sig-title {
            font-weight: 700;
            margin-bottom: 55px;
        }
        .sig-line {
            border-bottom: 1px solid #000;
            margin: 0 15px 4px;
        }
        .sig-name {
            font-weight: 700;
            font-size: 9px;
        }
    </style>
</head>

<body>
    @php
        /**
         * FUNGSI PEMBANTU BLADE
         * Fungsi: Membantu memformat data agar kode HTML tetap bersih dan mudah dibaca.
         * Letak: Didefinisikan di awal body agar bisa dipanggil di seluruh template.
         */

        // Format angka menjadi Rupiah Indonesia (contoh: 326901 -> "Rp 326.901")
        function formatRupiahLaporan($angka) {
            return 'Rp ' . number_format((float)$angka, 0, ',', '.');
        }

        // Format tanggal dari "31 May 2026 20:00" menjadi "31 Mei 2026"
        function formatTanggalLaporan($tgl) {
            if (!$tgl) return '-';
            try {
                return \Carbon\Carbon::parse($tgl)->translatedFormat('d M Y');
            } catch (\Throwable $e) {
                return $tgl;
            }
        }

        // Mengubah snake_case status menjadi label yang rapi
        // Contoh: "dalam_perjalanan" -> "Dalam Perjalanan"
        function statusLabelLaporan($status) {
            $map = [
                'pending'             => 'Menunggu',
                'diproses'            => 'Diproses',
                'dalam_perjalanan'    => 'Dalam Perjalanan',
                'tiba_di_kota_tujuan' => 'Tiba Di Kota Tujuan',
                'sedang_diantar'      => 'Sedang Diantar',
                'terkirim'            => 'Terkirim',
                'gagal'               => 'Gagal',
                'dibatalkan'          => 'Dibatalkan',
            ];
            return $map[strtolower($status)] ?? ucwords(str_replace('_', ' ', $status));
        }

        // Mengubah jenis_layanan menjadi label rapi
        function layananLabelLaporan($layanan) {
            $map = [
                'express' => 'Express',
                'reguler' => 'Reguler',
                'kargo'   => 'Kargo',
                'ekonomi' => 'Ekonomi',
            ];
            return $map[strtolower($layanan)] ?? ucfirst($layanan);
        }

        /**
         * Fungsi: Mengembalikan nama kelas CSS berdasarkan status pengiriman.
         * Cara Kerja: Setiap status dipetakan ke kelas CSS yang memiliki
         *             warna latar dan teks berbeda agar mudah dibedakan secara visual.
         * Letak: Digunakan di kolom STATUS pada tabel data.
         */
        function statusCssClass($status) {
            $map = [
                'terkirim'            => 'status-terkirim',
                'dalam_perjalanan'    => 'status-dalam-perjalanan',
                'diproses'            => 'status-diproses',
                'pending'             => 'status-pending',
                'tiba_di_kota_tujuan' => 'status-tiba-kota',
                'sedang_diantar'      => 'status-sedang-diantar',
                'gagal'               => 'status-gagal',
                'dibatalkan'          => 'status-dibatalkan',
            ];
            return $map[strtolower($status)] ?? 'status-pending';
        }

        // Label periode yang lebih mudah dibaca
        function periodeLabelLaporan($periode) {
            $map = [
                'hari_ini'   => 'Hari Ini',
                'minggu_ini' => 'Minggu Ini',
                'bulan_ini'  => 'Bulan Ini',
                'bulan_lalu' => 'Bulan Lalu',
                'tahun_ini'  => 'Tahun Ini',
                'custom'     => 'Rentang Khusus',
            ];
            return $map[$periode] ?? ucwords(str_replace('_', ' ', $periode));
        }

        // Menghitung grand total biaya dari seluruh data pada periode ini
        $grandTotal = collect($pengiriman)->sum('total_biaya');
    @endphp

    {{-- ═══════════════════════════════════════════════════════════════════
         BAGIAN 1: HEADER DOKUMEN
         Fungsi: Menampilkan identitas perusahaan dan info cetak di bagian atas.
         Cara Kerja: Tabel 2 kolom — kiri untuk brand, kanan untuk info cetak.
         ═══════════════════════════════════════════════════════════════════ --}}
    <table class="doc-header" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 50%;">
                <div class="brand-name">SuperStart</div>
                <div class="brand-desc">Sistem Manajemen Pengiriman Barang</div>
            </td>
            <td style="width: 50%;" class="header-info">
                <strong>LAPORAN DATA PENGIRIMAN</strong><br>
                Periode: {{ periodeLabelLaporan($filters['periode']) }}<br>
                Dicetak: {{ \Carbon\Carbon::parse($printedAt)->translatedFormat('d M Y, H:i') }} WIB
            </td>
        </tr>
    </table>

    {{-- ═══════════════════════════════════════════════════════════════════
         BAGIAN 2: RINGKASAN KPI (Key Performance Indicator)
         Fungsi: Menampilkan 4 metrik utama agar pembaca langsung paham
                 gambaran besar kondisi pengiriman pada periode yang difilter.
         Data: Diambil dari variabel $summary yang dikirim oleh LaporanController.
         ═══════════════════════════════════════════════════════════════════ --}}
    @if(isset($summary))
    <table class="kpi-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="kpi-label">Total Pengiriman</div>
                <div class="kpi-value">{{ number_format($summary['totalPengiriman'] ?? 0) }}</div>
            </td>
            <td>
                <div class="kpi-label">Total Pendapatan</div>
                <div class="kpi-value">{{ formatRupiahLaporan($summary['totalPendapatan'] ?? 0) }}</div>
            </td>
            <td>
                <div class="kpi-label">Berhasil Terkirim</div>
                <div class="kpi-value" style="color: #059669;">{{ number_format($summary['totalTerkirim'] ?? 0) }}</div>
            </td>
            <td>
                <div class="kpi-label">Gagal / Dibatalkan</div>
                <div class="kpi-value" style="color: #dc2626;">{{ number_format($summary['totalGagalBatal'] ?? 0) }}</div>
            </td>
        </tr>
    </table>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         BAGIAN 3: KOTAK INFO FILTER (PERIODE)
         Fungsi: Menampilkan parameter filter yang aktif saat laporan dicetak
                 agar pembaca tahu rentang waktu data yang disajikan.
         ═══════════════════════════════════════════════════════════════════ --}}
    <table class="meta-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="meta-key">Periode</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ periodeLabelLaporan($filters['periode']) }}</td>

            <td class="meta-key">Dari Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ formatTanggalLaporan($filters['dari']) }}</td>

            <td class="meta-key">Sampai Tanggal</td>
            <td class="meta-sep">:</td>
            <td class="meta-val">{{ formatTanggalLaporan($filters['sampai']) }}</td>
        </tr>
    </table>

    {{-- ═══════════════════════════════════════════════════════════════════
         BAGIAN 4: TABEL DATA PENGIRIMAN
         Fungsi: Menampilkan seluruh data pengiriman yang sudah difilter.
         Cara Kerja: Menggunakan 10 kolom dengan lebar proporsional yang
                     dijumlahkan menjadi tepat 100%.
         Proporsi Kolom (A4 Landscape = lebar efektif ~269mm):
           NO=3% | TGL=9% | RESI=14% | PENGIRIM=13% | ASAL=9%
           PENERIMA=13% | TUJUAN=9% | LAYANAN=8% | STATUS=10% | BIAYA=12%
         ═══════════════════════════════════════════════════════════════════ --}}
    <table class="data-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th style="width: 3%;"  class="center">NO</th>
                <th style="width: 9%;">TANGGAL</th>
                <th style="width: 14%;">NOMOR RESI</th>
                <th style="width: 13%;">PENGIRIM</th>
                <th style="width: 9%;">ASAL</th>
                <th style="width: 13%;">PENERIMA</th>
                <th style="width: 9%;">TUJUAN</th>
                <th style="width: 8%;"  class="center">LAYANAN</th>
                <th style="width: 10%;" class="center">STATUS</th>
                <th style="width: 12%;" class="right">TOTAL BIAYA</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pengiriman as $i => $p)
                <tr>
                    {{-- Kolom 1: Nomor urut --}}
                    <td class="center">{{ $i + 1 }}</td>

                    {{-- Kolom 2: Tanggal dibuat (format Indonesia) --}}
                    <td class="nowrap">{{ formatTanggalLaporan($p['created_at']) }}</td>

                    {{-- Kolom 3: Nomor resi (font tebal agar menonjol) --}}
                    <td class="bold">{{ $p['nomor_resi'] }}</td>

                    {{-- Kolom 4: Nama pengirim --}}
                    <td>{{ $p['pengirim_nama'] }}</td>

                    {{-- Kolom 5: Kota asal pengirim --}}
                    <td class="muted">{{ $p['asal_kota'] ?? '-' }}</td>

                    {{-- Kolom 6: Nama penerima --}}
                    <td>{{ $p['penerima_nama'] }}</td>

                    {{-- Kolom 7: Kota tujuan penerima --}}
                    <td class="muted">{{ $p['tujuan_kota'] ?? '-' }}</td>

                    {{-- Kolom 8: Jenis layanan (Express/Reguler/Kargo/Ekonomi) --}}
                    <td class="center">{{ layananLabelLaporan($p['jenis_layanan']) }}</td>

                    {{-- Kolom 9: Status pengiriman (HANYA BLOK WARNA tanpa teks) --}}
                    {{-- UPDATE: Sesuai instruksi, status ini cukup warna saja tanpa teks --}}
                    <td class="{{ statusCssClass($p['status']) }}">&nbsp;</td>

                    {{-- Kolom 10: Total biaya dalam format Rupiah --}}
                    <td class="right nowrap bold">{{ formatRupiahLaporan($p['total_biaya']) }}</td>
                </tr>
            @empty
                {{-- Tampilan jika tidak ada data sama sekali --}}
                <tr>
                    <td colspan="10" class="center" style="padding: 20px; color: #9ca3af;">
                        Tidak ada data pengiriman pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>

        {{-- ═══════════════════════════════════════════════════════════
             BARIS GRAND TOTAL
             Fungsi: Menampilkan penjumlahan seluruh biaya dari data yang dicetak.
             Letak: Di baris paling bawah tabel (tfoot).
             ═══════════════════════════════════════════════════════════ --}}
        @if (count($pengiriman) > 0)
        <tfoot>
            <tr class="grand-total">
                <td colspan="9" class="right">GRAND TOTAL ({{ count($pengiriman) }} pengiriman)</td>
                <td class="right nowrap">{{ formatRupiahLaporan($grandTotal) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>

    {{-- ═══════════════════════════════════════════════════════════════════
         BAGIAN 5: AREA TANDA TANGAN PENGESAHAN
         Fungsi: Menyediakan ruang resmi untuk tanda tangan pihak yang berwenang.
         Letak: Di bagian paling bawah dokumen, setelah tabel data.
         ═══════════════════════════════════════════════════════════════════ --}}
    @if (count($pengiriman) > 0)
    <table class="sig-table" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <div class="sig-title">Mengetahui,</div>
                <div class="sig-line">&nbsp;</div>
                <div class="sig-name">(________________________)</div>
                <div class="muted small">Pimpinan / Manager</div>
            </td>
            <td>
                <div class="sig-title">Menyetujui,</div>
                <div class="sig-line">&nbsp;</div>
                <div class="sig-name">(________________________)</div>
                <div class="muted small">Kepala Operasional</div>
            </td>
            <td>
                <div class="sig-title">Dibuat oleh,</div>
                <div class="sig-line">&nbsp;</div>
                <div class="sig-name">(________________________)</div>
                <div class="muted small">Admin SuperStart</div>
            </td>
        </tr>
    </table>
    @endif

    {{-- ═══════════════════════════════════════════════════════════════════
         BAGIAN 6: FOOTER DOKUMEN
         Fungsi: Catatan kaki berisi informasi bahwa dokumen dicetak otomatis.
         ═══════════════════════════════════════════════════════════════════ --}}
    <table class="doc-footer" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 60%;">
                Dokumen ini dicetak secara otomatis oleh sistem SuperStart.
                Tidak memerlukan tanda tangan basah.
            </td>
            <td style="width: 40%; text-align: right;">
                {{ \Carbon\Carbon::parse($printedAt)->translatedFormat('d F Y, H:i:s') }} WIB
            </td>
        </tr>
    </table>

</body>
</html>
