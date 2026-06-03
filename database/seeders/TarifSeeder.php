<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarifSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Data tarif universal (tanpa kota asal dan tujuan)
        // Perhitungan: Total = harga_dasar + (jarak * harga_per_km) + (berat * harga_per_kg)

        $tarifs = [
            [
                'jenis_layanan' => 'ekonomi',
                'harga_dasar' => 5000,
                'harga_per_km' => 50,
                'harga_per_kg' => 2000,
                'estimasi_hari' => 5,
                'status' => 'aktif',
            ],
            [
                'jenis_layanan' => 'reguler',
                'harga_dasar' => 10000,
                'harga_per_km' => 100,
                'harga_per_kg' => 4000,
                'estimasi_hari' => 3,
                'status' => 'aktif',
            ],
            [
                'jenis_layanan' => 'express',
                'harga_dasar' => 20000,
                'harga_per_km' => 250,
                'harga_per_kg' => 8000,
                'estimasi_hari' => 1,
                'status' => 'aktif',
            ],
            [
                'jenis_layanan' => 'kargo',
                'harga_dasar' => 50000,
                'harga_per_km' => 200,
                'harga_per_kg' => 1500, // Kargo lebih murah per KG
                'estimasi_hari' => 7,
                'status' => 'aktif',
            ],
        ];

        DB::table('tarif')->insert($tarifs);
    }
}
