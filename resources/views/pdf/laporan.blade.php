<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>LAPORAN OPERASIONAL PENGIRIMAN</title>
    <style>
        /* ── Base ───────────────────────────────────────────────── */
        /* UPDATE: Menggunakan font resmi Inter/Source Sans 3/Noto Sans */
        /* Fungsi: Menetapkan font stack untuk memastikan teks terlihat profesional. */
        @page {
            margin-top: 10mm;
            margin-bottom: 12mm;
            margin-left: 15mm;
            margin-right: 15mm;
        }

        body {
            font-family: 'Inter', 'Source Sans 3', 'Noto Sans', sans-serif;
            font-size: 10pt;
            color: #2a2a2a;
            /* Text: #2A2A2A */
            margin: 0;
            padding: 0;
        }

        /* ── Header Dokumen ───────────────────────────────────────── */
        .header-container {
            width: 100%;
            border-bottom: 2px solid #163b7a;
            /* Primary: #163B7A */
            padding-bottom: 5px;
            margin-bottom: 8px;
        }

        .header-logo {
            width: 75px;
            height: auto;
            margin-right: 15px;
            float: left;
        }

        .header-text {
            float: left;
            padding-top: 5px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #163b7a;
            margin: 0;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .company-tagline {
            font-size: 10pt;
            color: #475569;
            margin: 3px 0 5px 0;
        }

        .company-contact {
            font-size: 9pt;
            color: #475569;
        }

        .doc-type {
            float: right;
            padding-top: 5px;
            text-align: right;
            font-size: 10pt;
            color: #475569;
            font-weight: bold;
        }

        /* ── Judul Laporan ────────────────────────────────────────── */
        .title-container {
            text-align: center;
            margin-bottom: 10px;
        }

        .report-title {
            font-size: 18pt;
            /* Judul: 18pt */
            font-weight: bold;
            color: #163b7a;
            margin: 0 0 5px 0;
        }

        .report-period {
            font-size: 10pt;
            color: #475569;
        }

        /* ── Subjudul (Section) ───────────────────────────────────── */
        .section-title {
            font-size: 12pt;
            /* Subjudul: 12pt */
            font-weight: bold;
            color: #163b7a;
            margin: 8px 0 4px 0;
            text-transform: uppercase;
            border-bottom: 1px solid #d8dee8;
            /* Border: #D8DEE8 */
            padding-bottom: 3px;
        }

        /* ── List/Tabel Metadata (Informasi Laporan) ──────────────── */
        .meta-list {
            width: 100%;
            margin-bottom: 10px;
        }

        .meta-list td {
            padding: 4px 0;
            font-size: 10pt;
            vertical-align: top;
        }

        .meta-label {
            width: 200px;
            color: #475569;
            font-weight: bold;
        }

        .meta-separator {
            width: 20px;
            color: #475569;
        }

        .meta-value {
            color: #2a2a2a;
            font-weight: bold;
        }

        /* ── List/Tabel Ringkasan Operasional ─────────────────────── */
        .summary-list {
            width: 50%;
            margin-bottom: 10px;
        }

        .summary-list td {
            padding: 2px 0;
            font-size: 10pt;
        }

        .summary-label {
            width: 250px;
            color: #475569;
            font-weight: bold;
        }

        .summary-value {
            text-align: right;
            color: #2a2a2a;
            font-weight: bold;
        }

        /* ── Tabel Data Pengiriman ────────────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .data-table th {
            background-color: #163b7a;
            /* Header Navy: #163B7A */
            color: #ffffff;
            font-size: 10pt;
            /* Isi tabel: 10pt */
            font-weight: bold;
            text-align: left;
            padding: 5px 4px;
            border: 1px solid #163b7a;
        }

        .data-table td {
            font-size: 10pt;
            /* Isi putih (background) */
            color: #2a2a2a;
            /* Text: #2A2A2A */
            padding: 4px 4px;
            border: 1px solid #d8dee8;
            /* Border abu tipis: #D8DEE8 */
            vertical-align: top;
            background-color: #ffffff;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        /* ── Catatan Operasional ──────────────────────────────────── */
        .notes-box {
            font-size: 9.5pt;
            color: #475569;
            margin-bottom: 15px;
        }

        /* ── Tanda Tangan ─────────────────────────────────────────── */
        .sig-table {
            width: 100%;
            page-break-inside: avoid;
        }

        .sig-table td {
            width: 50%;
            font-size: 10pt;
            color: #2a2a2a;
            vertical-align: top;
        }

        .sig-title {
            margin-bottom: 40px;
        }

        .sig-name {
            font-weight: bold;
            text-decoration: underline;
        }

        /* ── Footer ───────────────────────────────────────────────── */
        .footer-table {
            width: 100%;
            margin-top: 10px;
            border-top: 1px solid #d8dee8;
            /* Border: #D8DEE8 */
            padding-top: 5px;
            font-size: 8pt;
            /* Footer: 8pt */
            color: #475569;
        }

        .page-number:before {
            content: counter(page);
        }
    </style>
</head>

<body>
    @php
        // [UPDATE: FUNGSI HELPER BLADE]
        // Fungsi: Membantu mengubah data mentah menjadi format rapi dan standar Indonesia
        function formatRupiah($angka)
        {
            return 'Rp ' . number_format((float) $angka, 0, ',', '.');
        }

        function formatTanggal($tgl)
        {
            if (!$tgl) {
                return '-';
            }
            try {
                return \Carbon\Carbon::parse($tgl)->translatedFormat('d M Y');
            } catch (\Throwable $e) {
                return $tgl;
            }
        }

        function statusLabel($status)
        {
            $map = [
                'pending' => 'Menunggu',
                'diproses' => 'Diproses',
                'dalam_perjalanan' => 'Dalam Perjalanan',
                'tiba_di_kota_tujuan' => 'Tiba Di Kota Tujuan',
                'sedang_diantar' => 'Sedang Diantar',
                'terkirim' => 'Terkirim',
                'gagal' => 'Gagal',
                'dibatalkan' => 'Dibatalkan',
            ];
            return $map[strtolower($status)] ?? ucwords(str_replace('_', ' ', $status));
        }

        function layananLabel($layanan)
        {
            $map = [
                'express' => 'Express',
                'reguler' => 'Reguler',
                'kargo' => 'Cargo',
                'ekonomi' => 'Ekonomi',
            ];
            return $map[strtolower($layanan)] ?? ucfirst($layanan);
        }

        // [UPDATE: AGGREGATE PERHITUNGAN RINGKASAN DATA]
        // Fungsi: Menghitung total resi, pendapatan, sukses/gagal langsung di view memory (aman krn <2000 data).
        $pengirimanCollection = collect($pengiriman);
        $totalResi = $pengirimanCollection->count();
        $totalPendapatan = $pengirimanCollection->sum('total_biaya');
        $pengirimanBerhasil = $pengirimanCollection->where('status', 'terkirim')->count();
        $pengirimanKendala = $pengirimanCollection->whereIn('status', ['gagal', 'dibatalkan'])->count();
        $jmlReguler = $pengirimanCollection->where('jenis_layanan', 'reguler')->count();
        $jmlExpress = $pengirimanCollection->where('jenis_layanan', 'express')->count();
        $jmlCargo = $pengirimanCollection->where('jenis_layanan', 'kargo')->count();
        $jmlEkonomi = $pengirimanCollection->where('jenis_layanan', 'ekonomi')->count();

        // [UPDATE: LOGO BASE64 GENERATOR]
        // Fungsi: Mencegah logo gagal dirender TCPDF akibat akses local path Windows dengan mengubahnya ke string Base64 murni.
        $logoPath = public_path('images/logo-softsend-hd.png');
        $logoData = '';
        if (file_exists($logoPath)) {
            $logoData = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
        }
    @endphp

    <!-- ─────────────────────────────────────────────────────────────
         HEADER DOKUMEN
         Sesuai Wireframe: Logo, Perusahaan, Tagline, dan Dokumen Internal
    ────────────────────────────────────────────────────────────── -->
    <div class="header-container">
        @if ($logoData)
            <img src="{{ $logoData }}" class="header-logo" alt="Logo">
        @else
            <div
                style="width: 75px; height: 75px; background-color: #1e293b; color: white; line-height: 75px; font-size: 24px; font-weight: bold; text-align:center; float: left; margin-right: 15px;">
                SS</div>
        @endif

        <div class="header-text">
            <div class="company-name">SOFTSEND LOGISTICS</div>
            <div class="company-tagline">Premium Delivery Management</div>
            <div class="company-contact">
                Jl. Sudirman No. 45, Jakarta Pusat, DKI Jakarta • (021) 555-0192 • www.softsend.co.id
            </div>
        </div>

        <div class="doc-type">
            Dokumen Internal
        </div>

        <div style="clear: both;"></div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
         JUDUL UTAMA LAPORAN
    ────────────────────────────────────────────────────────────── -->
    <div class="title-container">
        <div class="report-title">LAPORAN OPERASIONAL PENGIRIMAN</div>
        <div class="report-period">
            Periode : {{ formatTanggal($filters['dari']) }} – {{ formatTanggal($filters['sampai']) }}
        </div>
    </div>

    <!-- ─────────────────────────────────────────────────────────────
         INFORMASI LAPORAN (METADATA)
    ────────────────────────────────────────────────────────────── -->
    <div class="section-title">INFORMASI LAPORAN</div>
    <table class="meta-list" cellpadding="0" cellspacing="0">
        <tr>
            <td class="meta-label">No. Dokumen</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">SS-OPS-{{ \Carbon\Carbon::parse($printedAt)->format('Ymd') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Operator</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">Super Admin</td>
        </tr>
        <tr>
            <td class="meta-label">Dicetak</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">{{ \Carbon\Carbon::parse($printedAt)->translatedFormat('l, d F Y – H:i') }} WIB</td>
        </tr>
        <tr>
            <td class="meta-label">Lokasi</td>
            <td class="meta-separator">:</td>
            <td class="meta-value">Single Office</td>
        </tr>
    </table>

    <!-- ─────────────────────────────────────────────────────────────
         RINGKASAN OPERASIONAL
    ────────────────────────────────────────────────────────────── -->
    <div class="section-title">RINGKASAN OPERASIONAL</div>
    <table class="summary-list" cellpadding="0" cellspacing="0">
        <tr>
            <td class="summary-label">Total Pengiriman</td>
            <td class="summary-value">{{ number_format($totalResi, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Pengiriman Berhasil</td>
            <td class="summary-value">{{ number_format($pengirimanBerhasil, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Pengiriman Kendala</td>
            <td class="summary-value">{{ number_format($pengirimanKendala, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Total Pendapatan</td>
            <td class="summary-value">{{ formatRupiah($totalPendapatan) }}</td>
        </tr>
        <tr>
            <td colspan="2" style="height: 10px;"></td>
        </tr> <!-- Spacer -->
        <tr>
            <td class="summary-label">Layanan Reguler</td>
            <td class="summary-value">{{ number_format($jmlReguler, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Layanan Express</td>
            <td class="summary-value">{{ number_format($jmlExpress, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Layanan Cargo</td>
            <td class="summary-value">{{ number_format($jmlCargo, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="summary-label">Layanan Ekonomi</td>
            <td class="summary-value">{{ number_format($jmlEkonomi, 0, ',', '.') }}</td>
        </tr>
    </table>

    <!-- ─────────────────────────────────────────────────────────────
         DATA PENGIRIMAN UTAMA
    ────────────────────────────────────────────────────────────── -->
    <div class="section-title">DATA PENGIRIMAN</div>
    <table class="data-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th class="text-center" style="width:4%;">No</th>
                <th style="width:9%;">Tanggal</th>
                <th style="width:13%;">Nomor Resi</th>
                <th style="width:12%;">Pengirim</th>
                <th style="width:12%;">Penerima</th>
                <th style="width:10%;">Asal</th>
                <th style="width:10%;">Tujuan</th>
                <th class="text-center" style="width:9%;">Layanan</th>
                <th class="text-center" style="width:10%;">Status</th>
                <th class="text-right" style="width:11%;">Total Biaya</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pengiriman as $i => $p)
                <tr>
                    <td class="text-center">{{ $i + 1 }}</td>
                    <td>{{ formatTanggal($p['created_at']) }}</td>
                    <td style="font-weight: bold;">{{ $p['nomor_resi'] }}</td>
                    <td>{{ $p['pengirim_nama'] }}</td>
                    <td>{{ $p['penerima_nama'] }}</td>
                    <td>{{ $p['asal_kota'] ?? '-' }}</td>
                    <td>{{ $p['tujuan_kota'] ?? '-' }}</td>
                    <td class="text-center">{{ layananLabel($p['jenis_layanan']) }}</td>
                    <td class="text-center">{{ statusLabel($p['status']) }}</td>
                    <td class="text-right">{{ formatRupiah($p['total_biaya']) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center" style="padding: 20px;">Tidak ada data pengiriman pada periode
                        ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if (count($pengiriman) > 0)
            <tfoot>
                <tr>
                    <td colspan="9" class="text-right"
                        style="font-weight: bold; background-color: #f8fafc; padding-right: 10px;">GRAND TOTAL
                        ({{ count($pengiriman) }} Data)</td>
                    <td class="text-right" style="font-weight: bold; background-color: #f8fafc;">
                        {{ formatRupiah($totalPendapatan) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- ─────────────────────────────────────────────────────────────
         CATATAN OPERASIONAL & TANDA TANGAN
    ────────────────────────────────────────────────────────────── -->
    <div class="section-title">CATATAN OPERASIONAL</div>
    <div class="notes-box">
        Laporan ini dibuat otomatis berdasarkan data transaksi yang tersimpan pada sistem. Dokumen ini digunakan sebagai
        arsip operasional perusahaan.
    </div>

    <table class="sig-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="text-align: left;">
                <div class="sig-title">Disetujui Oleh:<br><strong>Pemilik Perusahaan</strong></div>
                <div class="sig-name">(...................................................)</div>
            </td>
            <td style="text-align: right;">
                <div class="sig-title">Diverifikasi Oleh:<br><strong>Manager Operasional</strong></div>
                <div class="sig-name">(...................................................)</div>
            </td>
        </tr>
    </table>

    <!-- ============================================================== -->
    <!-- FOOTER -->
    <!-- ============================================================== -->
    <table class="footer-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 70%;">
                <strong>Powered by NOCTRYNX CORP</strong><br>
                Dokumen dibuat otomatis oleh sistem.<br>
                Tidak memerlukan tanda tangan digital.
            </td>
            <td style="width: 30%; text-align: right; vertical-align: bottom;">Page <span class="page-number"></span>
            </td>
        </tr>
    </table>
</body>

</html>
