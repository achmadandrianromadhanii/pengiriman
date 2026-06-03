<?php

namespace App\Services;

use App\Models\Kota;
use App\Models\Tarif;
use Illuminate\Support\Facades\Cache;

class TarifService
{
    /**
     * Hitung tarif otomatis berdasarkan jarak (Haversine km) dan berat ditagihkan:
     * - Jarak dihitung dari latitude/longitude Kota Asal dan Tujuan
     * - Total Biaya = Harga Dasar + (Jarak * Harga per Km) + (Berat * Harga per Kg)
     *
     * @return array<int, array<string, mixed>>
     */
    public function hitung(int $asalId, int $tujuanId, float $beratKg, ?float $totalVolumeCm3 = null): array
    {
        // Cache object kota agar lebih cepat jika query berulang
        $kotaAsal = Cache::remember("kota_{$asalId}", 3600, fn () => Kota::find($asalId));
        $kotaTujuan = Cache::remember("kota_{$tujuanId}", 3600, fn () => Kota::find($tujuanId));

        // Hitung jarak menggunakan formula Haversine
        $jarakKm = 0;
        if ($kotaAsal && $kotaTujuan && $kotaAsal->latitude && $kotaAsal->longitude && $kotaTujuan->latitude && $kotaTujuan->longitude) {
            $jarakKm = $this->hitungJarak(
                (float) $kotaAsal->latitude,
                (float) $kotaAsal->longitude,
                (float) $kotaTujuan->latitude,
                (float) $kotaTujuan->longitude
            );
        }

        $beratAsli = max(0.01, (float) $beratKg);

        $volumeCm3 = $totalVolumeCm3 !== null ? max(0.0, (float) $totalVolumeCm3) : 0.0;
        $beratVolumetrik = $volumeCm3 > 0 ? round($volumeCm3 / 6000, 2) : 0.0;

        $beratDitagihkan = round(max($beratAsli, $beratVolumetrik), 2);
        $kgDitagih = max(1, (int) ceil($beratDitagihkan));

        // Ambil semua tarif layanan yang aktif dan pastikan unique per jenis layanan, dicache 1 jam
        $tarifList = Cache::remember('tarif_aktif_list', 3600, function () {
            return Tarif::query()
                ->where('status', 'aktif')
                ->orderBy('jenis_layanan')
                ->get()
                ->unique('jenis_layanan');
        });

        return $tarifList->map(function (Tarif $t) use ($jarakKm, $beratAsli, $beratVolumetrik, $beratDitagihkan, $kgDitagih) {
            $hargaDasar = (float) ($t->harga_dasar ?? 0);
            $hargaPerKm = (float) ($t->harga_per_km ?? 0);
            $hargaPerKg = (float) ($t->harga_per_kg ?? 0);

            // Perhitungan biaya: Harga Dasar + (Jarak * Harga per KM) + (Berat * Harga per KG)
            // Rumus ini fleksibel jika ada komponen yang nilainya 0.
            $totalHarga = $hargaDasar + ($jarakKm * $hargaPerKm) + ($kgDitagih * $hargaPerKg);

            return [
                'jenis_layanan' => (string) $t->jenis_layanan,
                'total' => (int) ceil($totalHarga),
                'harga_per_kg' => $hargaPerKg,
                'harga_per_km' => $hargaPerKm,
                'harga_dasar' => $hargaDasar,
                'jarak_km' => round($jarakKm, 2),
                'estimasi_hari' => (int) ($t->estimasi_hari ?? 0),

                // info tambahan (biar UI bisa jelasin cara hitungnya)
                'berat_asli' => $beratAsli,
                'berat_volumetrik' => $beratVolumetrik,
                'berat_ditagihkan' => $beratDitagihkan,
                'kg_ditagih' => $kgDitagih,
            ];
        })->values()->toArray();
    }

    /**
     * Hitung jarak Haversine antara dua titik koordinat (dalam km)
     * Ini fungsi matematika untuk mengukur jarak garis lurus di permukaan bumi.
     */
    private function hitungJarak(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        if ($lat1 === $lat2 && $lon1 === $lon2) {
            return 0.0; // Jarak 0 jika koordinat persis sama
        }

        $earthRadius = 6371; // Radius bumi dalam kilometer
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
