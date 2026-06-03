<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Services\PdfService;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Cache;
use Picqer\Barcode\BarcodeGeneratorSVG;

class ResiController extends Controller
{
    private const VOLUMETRIC_DIVIDER = 6000;

    public function print(Pengiriman $pengiriman)
    {
        return view('pdf.resi', $this->buildResiPayload($pengiriman, false));
    }

    public function pdf(Pengiriman $pengiriman)
    {
        $data = $this->buildResiPayload($pengiriman, true);
        $filename = 'Resi_'.$pengiriman->nomor_resi.'.pdf';

        return app(PdfService::class)->download('pdf.resi', $data, $filename);
    }

    /**
     * Payload view resi (untuk print & pdf).
     * Menjaga kompatibilitas dengan view lama (qrUrl, qrDataUri, barcodeSvg).
     */
    private function buildResiPayload(Pengiriman $pengiriman, bool $isPdf): array
    {
        $pengiriman->loadMissing([
            'barang',
            'pengirimKota:id,nama_kota,provinsi,kode_pos',
            'penerimaKota:id,nama_kota,provinsi,kode_pos',
        ]);

        $qrUrl = route('tracking.public', $pengiriman->nomor_resi);

        $qrDataUri = Cache::remember(
            'resi:qr2:'.$pengiriman->nomor_resi,
            now()->addDays(7),
            fn () => app(QrCodeService::class)->makeDataUri($pengiriman->nomor_resi, 160)
        );

        /*
         * BARCODE SVG — Code 128 (standar industri ekspedisi)
         * Parameter: widthFactor=1.8 (lebar tiap bar), height=50 (tinggi bar dalam px)
         * Alasan: widthFactor 1.8 menghasilkan barcode yang rapi dan proporsional
         *         di kertas thermal 100mm tanpa terlalu padat atau terlalu longgar.
         *         height 50 cukup untuk scanner barcode genggam membaca dari jarak 15cm.
         */
        $barcodeSvg = Cache::remember(
            'resi:bc2:'.$pengiriman->nomor_resi,
            now()->addDays(30),
            function () use ($pengiriman): string {
                $generator = new BarcodeGeneratorSVG;

                return $generator->getBarcode(
                    $pengiriman->nomor_resi,
                    $generator::TYPE_CODE_128,
                    1.8,
                    50
                );
            }
        );

        /*
         * LOGO DATA URI — Logo SoftSend (PNG transparan)
         * Fungsi: Meng-embed logo sebagai base64 data URI agar tidak butuh request HTTP.
         *         Ini penting untuk DomPDF (tidak bisa fetch URL) dan printer thermal.
         * Path: public/images/softsend-logo.png (logo utama SoftSend dengan sayap)
         */
        $logoDataUri = Cache::remember(
            'resi:logo:datauri:v2',
            now()->addDays(30),
            function (): ?string {
                $logoPath = public_path('images/softsend-logo.png');

                if (! is_file($logoPath)) {
                    return null;
                }

                $content = @file_get_contents($logoPath);

                if ($content === false) {
                    return null;
                }

                return 'data:image/png;base64,'.base64_encode($content);
            }
        );

        // Samakan dengan logika store()
        $beratAsli = $this->resolveBeratAsli($pengiriman);

        $totalVolumeCm3 = 0.0;
        $totalBeratVolumetrik = 0.0;

        $pengiriman->barang->transform(function ($barang) use (&$totalVolumeCm3, &$totalBeratVolumetrik) {
            $panjang = (float) ($barang->panjang_cm ?? 0);
            $lebar = (float) ($barang->lebar_cm ?? 0);
            $tinggi = (float) ($barang->tinggi_cm ?? 0);

            $volumeCm3 = round($panjang * $lebar * $tinggi, 2);
            $beratVolumetrikKg = $volumeCm3 > 0
                ? round($volumeCm3 / self::VOLUMETRIC_DIVIDER, 2)
                : 0.0;

            // Penjelasan: Menggunakan setAttribute alih-alih melakukan set property secara dinamis ($barang->volume_cm3 = ...)
            // untuk menghindari error undefined property di PHPStan, karena kolom ini mungkin tidak secara spesifik tercatat di PHPDoc class.
            $barang->setAttribute('volume_cm3', $volumeCm3);
            $barang->setAttribute('berat_volumetrik_kg', $beratVolumetrikKg);

            $totalVolumeCm3 += $volumeCm3;
            $totalBeratVolumetrik += $beratVolumetrikKg;

            return $barang;
        });

        $totalVolumeCm3 = round($totalVolumeCm3, 2);
        $beratVolumetrik = round($totalBeratVolumetrik, 2);
        $beratDitagihkan = $this->calcChargeableKg($beratAsli, $beratVolumetrik);

        return [
            // kompatibel dengan blade saat ini
            'pengiriman' => $pengiriman,
            'qrUrl' => $qrUrl,
            'qrDataUri' => $qrDataUri,
            'barcodeSvg' => $barcodeSvg,

            // tambahan untuk blade
            'trackingUrl' => $qrUrl,
            'isPdf' => $isPdf,
            'printedAt' => now(),
            'totalBerat' => $beratDitagihkan,
            'beratAsli' => $beratAsli,
            'beratVolumetrik' => $beratVolumetrik,
            'totalVolumeCm3' => $totalVolumeCm3,
            'dividerVolumetrik' => self::VOLUMETRIC_DIVIDER,
            'logoDataUri' => $logoDataUri,
        ];
    }

    /**
     * Berat asli mengikuti data yang disimpan saat store():
     * 'total_berat_kg' => $totalBeratAsli
     */
    private function resolveBeratAsli(Pengiriman $pengiriman): float
    {
        $beratAsli = (float) ($pengiriman->total_berat_kg ?? 0);

        if ($beratAsli > 0) {
            return round($beratAsli, 2);
        }

        // fallback untuk data lama
        $fromBarang = (float) $pengiriman->barang->sum(
            fn ($barang): float => (float) ($barang->berat_kg ?? 0)
        );

        return max(0.01, round($fromBarang, 2));
    }

    /**
     * Chargeable weight = max(berat asli, berat volumetrik)
     * Disamakan dengan store().
     */
    private function calcChargeableKg(float $beratAsli, float $beratVolumetrik): float
    {
        return round(max($beratAsli, $beratVolumetrik), 2);
    }
}
