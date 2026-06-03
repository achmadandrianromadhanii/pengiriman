<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Kota;
use App\Models\Pengiriman;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DummyLaporanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = Admin::first();
        if (! $admin) {
            $this->command->error('Tidak ada admin di database. Buat admin terlebih dahulu.');

            return;
        }

        $kotas = Kota::inRandomOrder()->limit(10)->pluck('id')->toArray();
        if (count($kotas) < 2) {
            $this->command->error('Minimal butuh 2 kota di database.');

            return;
        }

        $layanan = ['reguler', 'express', 'kargo', 'ekonomi'];
        $statusEnum = ['pending', 'diproses', 'dalam_perjalanan', 'tiba_di_kota_tujuan', 'sedang_diantar', 'terkirim', 'gagal', 'dibatalkan'];

        $jumlahData = 100; // Jumlah data dummy yang akan dibuat

        $this->command->info("Membuat {$jumlahData} data dummy pengiriman (ditandai dengan catatan: DUMMY_LAPORAN)...");

        $dataBatch = [];
        $now = Carbon::now();

        for ($i = 0; $i < $jumlahData; $i++) {
            $asal = $kotas[array_rand($kotas)];
            $tujuan = $kotas[array_rand($kotas)];
            while ($asal === $tujuan) {
                $tujuan = $kotas[array_rand($kotas)];
            }

            $currentStatus = $statusEnum[array_rand($statusEnum)];
            $isTerkirim = $currentStatus === 'terkirim';
            $isBatal = $currentStatus === 'dibatalkan';

            // Random tanggal antara 30 hari yang lalu sampai hari ini
            $createdAt = (clone $now)->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $estimasiTiba = (clone $createdAt)->addDays(rand(1, 5));

            $tglTerkirim = null;
            if ($isTerkirim) {
                // Terkadang lebih cepat, terkadang terlambat
                $tglTerkirim = (clone $createdAt)->addDays(rand(1, 6))->addHours(rand(1, 10));
            }

            $biaya = rand(10, 50) * 1000;
            $tambahan = rand(0, 1) === 1 ? rand(5, 15) * 1000 : 0;
            $asuransi = rand(0, 1) === 1 ? rand(1, 5) * 1000 : 0;

            $dataBatch[] = [
                'nomor_resi' => 'DUMMY'.strtoupper(Str::random(8)).$i,
                'pengirim_nama' => 'Pengirim Dummy '.rand(1, 100),
                'pengirim_hp' => '0812'.rand(10000000, 99999999),
                'pengirim_alamat' => 'Alamat Dummy Pengirim',
                'pengirim_kota_id' => $asal,

                'penerima_nama' => 'Penerima Dummy '.rand(1, 100),
                'penerima_hp' => '0812'.rand(10000000, 99999999),
                'penerima_alamat' => 'Alamat Dummy Penerima',
                'penerima_kota_id' => $tujuan,

                'total_berat_kg' => rand(10, 50) / 10, // 1.0 - 5.0 kg
                'jumlah_barang' => rand(1, 3),
                'jenis_layanan' => $layanan[array_rand($layanan)],

                'biaya_pengiriman' => $biaya,
                'biaya_tambahan' => $tambahan,
                'biaya_asuransi' => $asuransi,
                'total_biaya' => $biaya + $tambahan + $asuransi,

                'metode_pembayaran' => 'dibayar_pengirim',
                'status_pembayaran' => 'lunas',
                'status' => $currentStatus,
                'estimasi_tiba' => $estimasiTiba->toDateString(),
                'tanggal_terkirim' => $tglTerkirim ? $tglTerkirim->toDateTimeString() : null,
                'catatan' => 'DUMMY_LAPORAN', // TANDA KHUSUS UNTUK MUDAH DIHAPUS
                'alasan_batal' => $isBatal ? 'Batal dari dummy sistem' : null,
                'admin_id' => $admin->id,

                'created_at' => $createdAt->toDateTimeString(),
                'updated_at' => (clone $createdAt)->addMinutes(5)->toDateTimeString(),
            ];
        }

        // Insert massal agar cepat
        foreach (array_chunk($dataBatch, 200) as $chunk) {
            Pengiriman::insert($chunk);
        }

        $this->command->info("Berhasil membuat {$jumlahData} data dummy! Tanda pengenalnya ada di kolom catatan = DUMMY_LAPORAN.");
        $this->command->info("Untuk menghapusnya nanti, cukup jalankan: php artisan tinker --execute=\"App\Models\Pengiriman::where('catatan', 'DUMMY_LAPORAN')->delete();\"");
    }
}
