<!doctype html>
<html lang="id">
<!--
  RESI THERMAL — FORMAT PREMIUM MIRIP J&T EXPRESS
  ================================================================
  Fungsi  : Label pengiriman (waybill) untuk dicetak di printer thermal (100x150mm)
            maupun kertas A4 biasa untuk keperluan testing.
  Desain  : Mengikuti layout resi J&T Express asli:
            1. Header: Logo brand BESAR + tanggal (kanan atas)
            2. Barcode Code128 RAPI + QR Code bersebelahan
            3. Routing Code RAKSASA + Service Badge
            4. Alamat Penerima & Pengirim (dengan label hitam)
            5. Pembayaran COD/NON-COD + Ongkir + Estimasi
            6. Isi Paket (Koli, Qty, Berat)
            7. Tanda Tangan (Pengirim + QR + Penerima)
  Ukuran  : CSS @page { size: 100mm 150mm } untuk kertas thermal standar.
  Barcode : widthFactor=1.8, height=50 (rapi, proporsional, bisa discan).
-->
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resi {{ $pengiriman->nomor_resi }} — SoftSend</title>

    <style>
        /*
         * ═══════════════════════════════════════════════════════════
         * UKURAN KERTAS THERMAL (100mm x 150mm)
         * Fungsi: Memaksa browser/DomPDF menggunakan dimensi kertas thermal A6.
         *         Jika dicetak di A4, label akan muncul di pojok kiri atas.
         * ═══════════════════════════════════════════════════════════
         */
        @page {
            size: 100mm 150mm;
            margin: 0;
        }

        /* Reset CSS dasar — konsistensi di semua browser & DomPDF */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #000;
            background: #e2e8f0; /* Latar belakang abu-abu saat di browser */
            -webkit-print-color-adjust: exact; /* Warna hitam pekat tercetak sempurna */
            print-color-adjust: exact;
            margin: 0;
            padding: 20px 0; /* Jarak atas-bawah saat di browser */
        }

        /*
         * ═══════════════════════════════════════════════════════════
         * PEMBUNGKUS UTAMA (Area Kertas Thermal)
         * Fungsi: Membatasi lebar konten ke 100mm (lebar kertas thermal standar).
         *         Padding 1mm agar konten tidak terpotong di tepi kertas.
         * ═══════════════════════════════════════════════════════════
         */
        .thermal-wrap {
            width: 100mm;
            /* max-height dihapus agar kertas memanjang otomatis tanpa menumpahkan konten */
            padding: 2mm;
            margin: 0 auto; /* Otomatis ke TENGAH layar */
            background: #fff;
            box-sizing: border-box;
            page-break-inside: avoid;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2); /* Efek bayangan kertas */
        }

        /*
         * KOTAK LUAR RESI (Border Hitam Tebal — khas waybill ekspedisi)
         * Fungsi: Memberikan bingkai tegas agar resi terlihat profesional.
         */
        .waybill {
            border: 2px solid #000;
            width: 100%;
        }

        /*
         * SEKSI — Setiap baris horizontal dipisahkan garis hitam
         * Fungsi: Pembatas visual antar bagian resi (header, barcode, alamat, dll).
         */
        .sec {
            border-bottom: 1.5px solid #000;
            padding: 1.5mm 2mm;
        }
        .sec:last-child { border-bottom: none; }
        .sec-np { padding: 0; } /* Seksi tanpa padding (untuk routing code) */

        /* Tabel utilitas — pengganti flexbox agar DomPDF kompatibel */
        .tbl { width: 100%; border-collapse: collapse; }
        .tbl td { vertical-align: middle; border: none; padding: 0; }

        /*
         * ═══════════════════════════════════════════════════════════
         * SEKSI 1: HEADER — Logo SoftSend (BESAR) + Tanggal
         * Desain: Logo lebar penuh di kiri, tanggal kecil di kanan atas.
         *         Logo diperbesar ke 18mm tinggi agar HD dan tajam di thermal.
         * ═══════════════════════════════════════════════════════════
         */
        .hdr-logo {
            height: 20mm;           /* Ukuran optimal agar logo besar tapi tidak memakan ruang vertikal berlebih */
            width: 100%;            /* Paksa melebar sejauh mungkin di kolomnya */
            max-width: 65mm;
            object-fit: contain;
            object-position: left;  /* Pastikan logo rata kiri, tidak di tengah kotak */
            vertical-align: middle;
            display: block;
        }
        .hdr-date {
            font-size: 7px;
            font-weight: bold;
            text-align: right;
            line-height: 1.5;
            color: #222;
            vertical-align: top;
        }

        /*
         * ═══════════════════════════════════════════════════════════
         * SEKSI 2: BARCODE + QR CODE (Bersebelahan, gaya J&T)
         * Desain: Barcode Code128 di kiri (dominan), QR Code kecil di kanan.
         *         SVG barcode dibatasi ke 55mm lebar × 12mm tinggi agar rapi.
         * ═══════════════════════════════════════════════════════════
         */
        .bc-area {
            text-align: center;
            padding: 1mm 0;
        }
        .bc-area svg {
            width: 55mm;          /* Lebar barcode — pas di area thermal */
            height: 12mm;         /* Tinggi barcode — proporsional untuk scanner */
            display: block;
            margin: 0 auto;
        }
        .bc-resi {
            font-size: 12px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-top: 0.5mm;
            text-align: center;
        }
        .bc-qr {
            text-align: center;
            vertical-align: middle;
            width: 22mm;
        }
        .bc-qr img {
            width: 18mm;
            height: 18mm;
        }

        /*
         * ═══════════════════════════════════════════════════════════
         * SEKSI 3: ROUTING CODE RAKSASA + SERVICE BADGE
         * Fungsi: Petugas gudang bisa membaca kota tujuan dari jarak 2 meter.
         *         Warna hitam solid pada service badge agar kontras tinggi.
         * ═══════════════════════════════════════════════════════════
         */
        .rt-wrap { height: 13mm; }
        .rt-code {
            font-size: 24px; /* Diperkecil sedikit agar hemat ruang vertikal */
            font-weight: 900;
            text-align: center;
            vertical-align: middle;
            text-transform: uppercase;
            border-right: 2px solid #000;
            letter-spacing: 1px;
        }
        .rt-svc {
            width: 24mm;
            text-align: center;
            vertical-align: middle;
            background: #000;
            color: #fff;
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 1px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /*
         * ═══════════════════════════════════════════════════════════
         * SEKSI 4: ALAMAT PENERIMA & PENGIRIM
         * Desain: Label hitam solid ("PENERIMA"/"PENGIRIM") + data alamat.
         *         Mirip J&T: label kapsul hitam, nama tebal, telepon & alamat di bawah.
         * ═══════════════════════════════════════════════════════════
         */
        .addr-label {
            font-size: 7px;
            font-weight: 900;
            text-transform: uppercase;
            background: #000;
            color: #fff;
            padding: 0.8mm 2mm;
            display: inline-block;
            margin-bottom: 0.5mm;
            letter-spacing: 0.5px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .addr-name {
            font-size: 10px;
            font-weight: 900;
            line-height: 1.1;
        }
        .addr-phone {
            font-size: 9px;
            font-weight: bold;
            color: #333;
        }
        .addr-detail {
            font-size: 8px;
            line-height: 1.2;
            margin-top: 0.5mm;
        }
        .addr-city {
            font-size: 8.5px;
            font-weight: 900;
            margin-top: 0.5mm;
            text-transform: uppercase;
        }
        .addr-divider {
            border-top: 1px dashed #000;
            margin: 1.5mm 0;
        }

        /*
         * ═══════════════════════════════════════════════════════════
         * SEKSI 5: PEMBAYARAN (COD / NON-COD + Ongkir + Estimasi Tiba)
         * Desain: Background abu-abu muda agar area pembayaran menonjol.
         *         COD ditampilkan besar dan tebal agar kurir langsung tahu.
         * ═══════════════════════════════════════════════════════════
         */
        .pay-wrap {
            background: #f0f0f0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .pay-cod {
            font-size: 16px;
            font-weight: 900;
        }
        .pay-noncod {
            font-size: 13px;
            font-weight: 900;
        }
        .pay-status {
            font-size: 8px;
            font-weight: bold;
            margin-top: 0.5mm;
        }
        .pay-info {
            font-size: 7.5px;
            font-weight: bold;
            text-align: right;
            line-height: 1.5;
        }

        /*
         * ═══════════════════════════════════════════════════════════
         * SEKSI 6: ISI PAKET (Koli, Qty, Berat + Detail isi)
         * Desain: Info ringkas 1 baris (Koli | Qty | Berat) di atas,
         *         detail isi paket di bawah. Mirip resi J&T yang hemat ruang.
         * ═══════════════════════════════════════════════════════════
         */
        .pkg-row { font-size: 9px; line-height: 1.3; }
        .pkg-meta {
            font-size: 8.5px;
            margin-bottom: 0.8mm;
            border-bottom: 1px dotted #999;
            padding-bottom: 0.8mm;
        }
        .pkg-meta b { font-weight: 900; }
        .pkg-items { font-size: 8.5px; font-weight: bold; line-height: 1.3; }
        .pkg-note { font-size: 7.5px; font-style: italic; margin-top: 0.5mm; color: #333; }

        /*
         * ═══════════════════════════════════════════════════════════
         * SEKSI 7: TANDA TANGAN (Pengirim + QR Tengah + Penerima)
         * Fungsi: Proof of Delivery (PoD) — bukti serah terima paket.
         *         QR di tengah untuk scan tracking oleh kurir saat antar.
         * ═══════════════════════════════════════════════════════════
         */
        .sig-tbl td {
            text-align: center;
            vertical-align: top;
            padding: 0.5mm 0.5mm;
            font-size: 7px;
            font-weight: bold;
        }
        .sig-line {
            border-bottom: 1px dashed #000;
            height: 8mm;
            margin: 0 1.5mm;
        }
        .sig-qr img {
            width: 15mm;
            height: 15mm;
            margin-top: 0.5mm;
        }
        .sig-label {
            font-size: 6.5px;
            color: #555;
            margin-top: 0.3mm;
        }
        .sig-name {
            font-size: 7px;
            margin-top: 0.3mm;
        }

        /*
         * GARIS POTONG (Cut Line)
         * Fungsi: Panduan gunting manual atau auto-cut printer thermal.
         */
        .cut-line {
            border-bottom: 1px dashed #999;
            margin: 1mm 0 0;
            width: 100%;
        }

        /*
         * ═══════════════════════════════════════════════════════════
         * TOMBOL AKSI (Hanya tampil di browser, hilang saat print/PDF)
         * Fungsi: Cetak, Download PDF, Tutup tab — untuk kemudahan user.
         * ═══════════════════════════════════════════════════════════
         */
        .no-print {
            width: 100mm; /* Samakan lebarnya dengan resi */
            margin: 0 auto 15px; /* Berada di tengah layar */
            padding: 8px; /* Dikurangi sedikit agar area lebih luas */
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            font-family: sans-serif;
            box-sizing: border-box;
            /* Flexbox agar tombol sejajar rapi */
            display: flex;
            justify-content: center;
            gap: 6px; /* Jarak antar tombol diperkecil */
        }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 4px; /* Jarak ikon & teks diperkecil */
            padding: 6px 10px; border-radius: 6px; /* Padding & border-radius diperkecil */
            border: 1px solid #cbd5e1; text-decoration: none;
            color: #0f172a; background: #fff;
            font-size: 11px; font-weight: bold; cursor: pointer; /* Font size diperkecil */
        }
        .btn-primary { background: #000; color: #fff; border-color: #000; }

        /* Saat dicetak, kembalikan semua ke setelan murni kertas */
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; padding: 0; }
            .thermal-wrap { margin: 0; padding: 0; box-shadow: none; }
        }
    </style>
</head>

<body>
    {{-- ══════════════════════════════════════════════════════════
         TOMBOL CETAK & DOWNLOAD (Hanya tampil di browser)
         Fungsi: Memberikan aksi cepat tanpa harus Ctrl+P.
         Hilang otomatis saat print / generate PDF.
         ══════════════════════════════════════════════════════════ --}}
    @if (!$isPdf)
    <div class="no-print">
        <button class="btn btn-primary" onclick="window.print()">🖨 Cetak Thermal</button>
        <a class="btn" href="{{ route('resi.pdf', $pengiriman) }}">📄 Download PDF</a>
        {{--
            Tombol Tutup: Menutup tab resi (yang dibuka via window.open '_blank').
            Fallback: Jika bukan popup, gunakan history.back().
        --}}
        <button class="btn" onclick="window.close(); setTimeout(function(){ history.back(); }, 300);">← Tutup</button>
    </div>
    @endif

    @php
        /*
         * ═══════════════════════════════════════════════════════════
         * LOGIKA PENDUKUNG BLADE
         * Fungsi: Menyiapkan semua variabel yang dibutuhkan template HTML
         *         di satu tempat agar template di bawah tetap bersih.
         * ═══════════════════════════════════════════════════════════
         */

        // Nama kota (uppercase) untuk routing code dan alamat
        $asalKota   = strtoupper(optional($pengiriman->pengirimKota)->nama_kota ?? 'ASAL');
        $tujuanKota = strtoupper(optional($pengiriman->penerimaKota)->nama_kota ?? 'TUJUAN');

        // Routing Code: 3 huruf pertama kota asal - 3 huruf pertama kota tujuan
        // Contoh: JAKARTA -> JAK, SURABAYA -> SUR => "JAK-SUR"
        $routeCode = substr($asalKota, 0, 3) . '-' . substr($tujuanKota, 0, 3);

        // Service Code: Singkatan layanan (mirip J&T: REG, ECO, EXP, CRG)
        $svc     = strtoupper($pengiriman->jenis_layanan);
        $svcMap  = ['REGULER' => 'REG', 'EKONOMI' => 'ECO', 'EXPRESS' => 'EXP', 'KARGO' => 'CRG'];
        $svcCode = $svcMap[$svc] ?? strtoupper(substr($pengiriman->jenis_layanan, 0, 3));

        // Rangkuman isi paket menjadi 1 baris teks hemat ruang
        $totalBarang   = $pengiriman->barang->count();
        $namaBarangArr = [];
        foreach ($pengiriman->barang as $b) {
            $namaBarangArr[] = $b->nama_barang . ($b->keterangan ? ' (' . $b->keterangan . ')' : '');
        }
        $isiPaket = implode(', ', $namaBarangArr);

        // Apakah metode COD atau bukan
        $isCod = strtolower($pengiriman->metode_pembayaran) === 'cod';

        // Status Pembayaran: LUNAS atau BELUM LUNAS
        $statusBayar = strtoupper($pengiriman->status_pembayaran ?? 'lunas');

        // Tanggal pengiriman (dibuat) & estimasi tiba
        $tglKirim    = \Carbon\Carbon::parse($pengiriman->created_at)->format('d-m-Y');
        $tglEstimasi = optional($pengiriman->estimasi_tiba)->format('d-m-Y') ?? '-';

        // Provinsi (uppercase)
        $provAsal   = strtoupper(optional($pengiriman->pengirimKota)->provinsi ?? '');
        $provTujuan = strtoupper(optional($pengiriman->penerimaKota)->provinsi ?? '');

        // Kode pos
        $kpAsal   = optional($pengiriman->pengirimKota)->kode_pos ?? '';
        $kpTujuan = optional($pengiriman->penerimaKota)->kode_pos ?? '';
    @endphp

    <div class="thermal-wrap">
        <div class="waybill">

            {{-- ══════════════════════════════════════════════════════
                 SEKSI 1: HEADER — Logo SoftSend (BESAR) + Tanggal
                 Tata Letak: Logo di kiri (18mm tinggi, HD), tanggal di kanan atas.
                 Logo menggunakan data URI base64 agar kompatibel DomPDF & thermal.
                 ══════════════════════════════════════════════════════ --}}
            <div class="sec">
                <table class="tbl">
                    <tr>
                        {{-- Logo SoftSend — BESAR, HD, Tajam --}}
                        <td style="width: 55%;">
                            @if ($logoDataUri)
                                <img src="{{ $logoDataUri }}" alt="SoftSend" class="hdr-logo">
                            @endif
                        </td>
                        {{-- Tanggal Kirim & Cetak (kanan atas) --}}
                        <td class="hdr-date">
                            Tgl Kirim: {{ $tglKirim }}<br>
                            Cetak: {{ now()->timezone('Asia/Jakarta')->format('d-m-Y H:i') }}
                        </td>
                    </tr>
                </table>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 SEKSI 2: BARCODE + QR CODE (Bersebelahan, gaya J&T)
                 Tata Letak: Barcode Code128 di kiri (55mm), QR Code 18mm di kanan.
                 Barcode: SVG rapi (widthFactor=1.8, height=50) dari controller.
                 QR Code: Data URI PNG untuk scan tracking cepat oleh petugas gudang.
                 ══════════════════════════════════════════════════════ --}}
            <div class="sec">
                <table class="tbl">
                    <tr>
                        {{-- Barcode + Nomor Resi (kiri, dominan) --}}
                        <td class="bc-area">
                            {!! $barcodeSvg !!}
                            <div class="bc-resi">{{ $pengiriman->nomor_resi }}</div>
                        </td>
                        {{-- QR Code (kanan, kecil, untuk scan cepat petugas gudang) --}}
                        <td class="bc-qr">
                            <img src="{{ $qrDataUri }}" alt="QR">
                        </td>
                    </tr>
                </table>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 SEKSI 3: ROUTING CODE RAKSASA + SERVICE BADGE
                 Fungsi: Petugas gudang bisa baca arah tujuan dari jarak 2 meter.
                 Tata Letak: Kode rute (misal: JAK-SUR) mengisi 75% lebar,
                             badge layanan (REG/EXP/ECO/CRG) mengisi 25% kanan.
                 ══════════════════════════════════════════════════════ --}}
            <div class="sec sec-np">
                <table class="tbl rt-wrap">
                    <tr>
                        <td class="rt-code">{{ $routeCode }}</td>
                        <td class="rt-svc">{{ $svcCode }}</td>
                    </tr>
                </table>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 SEKSI 4: ALAMAT PENERIMA & PENGIRIM
                 Tata Letak: Penerima SELALU di atas (karena ini tujuan utama kurir).
                             Dipisahkan garis putus-putus. Pengirim di bawah.
                             Masing-masing punya label kapsul hitam khas J&T.
                 ══════════════════════════════════════════════════════ --}}
            <div class="sec">
                {{-- Penerima (di atas — target utama kurir) --}}
                <div>
                    <span class="addr-label">PENERIMA</span>
                    <div class="addr-name">{{ $pengiriman->penerima_nama }}</div>
                    <div class="addr-phone">{{ $pengiriman->penerima_hp }}</div>
                    <div class="addr-detail">{{ $pengiriman->penerima_alamat }}</div>
                    <div class="addr-city">
                        {{ $tujuanKota }}, {{ $provTujuan }} {{ $kpTujuan }}
                    </div>
                </div>

                <div class="addr-divider"></div>

                {{-- Pengirim (di bawah — lengkap dengan alamat jalan) --}}
                <div>
                    <span class="addr-label">PENGIRIM</span>
                    <div class="addr-name">{{ $pengiriman->pengirim_nama }}</div>
                    <div class="addr-phone">{{ $pengiriman->pengirim_hp }}</div>
                    <div class="addr-detail">{{ $pengiriman->pengirim_alamat }}</div>
                    <div class="addr-city">
                        {{ $asalKota }}, {{ $provAsal }} {{ $kpAsal }}
                    </div>
                </div>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 SEKSI 5: PEMBAYARAN (COD / NON-COD + Status + Estimasi Tiba)
                 Tata Letak: Info COD/NON-COD besar di kiri (55%),
                             Ongkir + Estimasi tiba di kanan (45%).
                             Background abu-abu muda agar area ini menonjol.
                 ══════════════════════════════════════════════════════ --}}
            <div class="sec pay-wrap">
                <table class="tbl">
                    <tr>
                        {{-- Indikator COD / NON-COD (kiri) --}}
                        <td style="width: 55%;">
                            @if($isCod)
                                <div class="pay-cod">COD: Rp {{ number_format((float) $pengiriman->total_biaya, 0, ',', '.') }}</div>
                            @else
                                <div class="pay-noncod">NON-COD</div>
                            @endif
                            <div class="pay-status">Bayar: {{ $statusBayar }}</div>
                        </td>
                        {{-- Info Ongkir + Estimasi Tiba (kanan) --}}
                        <td class="pay-info">
                            Ongkir: Rp {{ number_format((float) $pengiriman->biaya_pengiriman, 0, ',', '.') }}<br>
                            Est. Tiba: {{ $tglEstimasi }}
                        </td>
                    </tr>
                </table>
            </div>

            {{-- ══════════════════════════════════════════════════════
                 SEKSI 6: ISI PAKET (Koli + Qty + Berat + Detail Isi)
                 Tata Letak: Info ringkas (Koli | Qty | Berat) di baris atas,
                             Detail isi paket di bawah (dipotong max 100 karakter).
                 ══════════════════════════════════════════════════════ --}}
            <div class="sec pkg-row">
                <div class="pkg-meta">
                    <b>Koli: 1/1</b> &nbsp;|&nbsp;
                    <b>Qty: {{ $totalBarang }} pcs</b> &nbsp;|&nbsp;
                    <b>Berat: {{ number_format($totalBerat, 2) }} kg</b>
                </div>
                <div class="pkg-items">
                    Isi: {{ \Illuminate\Support\Str::limit($isiPaket, 100, '...') }}
                </div>
                @if($pengiriman->catatan)
                    <div class="pkg-note">Catatan: {{ $pengiriman->catatan }}</div>
                @endif
            </div>

            {{-- ══════════════════════════════════════════════════════
                 SEKSI 7: TANDA TANGAN (Pengirim + QR Tengah + Penerima)
                 Fungsi: Proof of Delivery (PoD) — bukti serah terima paket.
                 Tata Letak: 3 kolom — Ttd Pengirim (35%) | QR Scan (30%) | Ttd Penerima (35%)
                 ══════════════════════════════════════════════════════ --}}
            <div class="sec">
                <table class="tbl sig-tbl">
                    <tr>
                        {{-- Kolom Tanda Tangan Pengirim --}}
                        <td style="width: 35%;">
                            <div>Ttd Pengirim</div>
                            <div class="sig-line"></div>
                            <div class="sig-name">{{ \Illuminate\Support\Str::limit($pengiriman->pengirim_nama, 18) }}</div>
                        </td>
                        {{-- QR Code Tengah (untuk scan kurir saat pengantaran) --}}
                        <td class="sig-qr" style="width: 30%;">
                            <img src="{{ $qrDataUri }}" alt="QR">
                            <div class="sig-label">Scan Tracking</div>
                        </td>
                        {{-- Kolom Tanda Tangan Penerima --}}
                        <td style="width: 35%;">
                            <div>Ttd Penerima</div>
                            <div class="sig-line"></div>
                            <div class="sig-name">Nama Terang</div>
                        </td>
                    </tr>
                </table>
            </div>

        </div><!-- /.waybill -->

        {{-- ══════════════════════════════════════════════════════
             GARIS POTONG (Cut Line) — Panduan gunting / auto-cut printer thermal
             ══════════════════════════════════════════════════════ --}}
        <div class="cut-line"></div>

    </div><!-- /.thermal-wrap -->
</body>
</html>