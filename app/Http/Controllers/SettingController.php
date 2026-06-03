<?php

namespace App\Http\Controllers;

use App\Models\Kota;
use App\Models\Tarif;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(Request $request): Response
    {
        $tab = (string) $request->query('tab', 'kota');
        if (! in_array($tab, ['kota', 'tarif'], true)) {
            $tab = 'kota';
        }

        $kotaList = Kota::query()
            ->orderBy('nama_kota')
            ->get(['id', 'nama_kota', 'provinsi', 'kode_pos', 'latitude', 'longitude', 'status', 'created_at']);

        $tarifList = Tarif::query()
            ->orderByDesc('updated_at')
            ->get([
                'id',
                'jenis_layanan',
                'harga_dasar',
                'harga_per_km',
                'harga_per_kg',
                'estimasi_hari',
                'status',
                'created_at',
                'updated_at',
            ])
            ->map(static function (Tarif $t): array {
                return [
                    'id' => $t->id,
                    'jenis_layanan' => $t->jenis_layanan,
                    'harga_dasar' => (float) $t->harga_dasar,
                    'harga_per_km' => (float) $t->harga_per_km,
                    'harga_per_kg' => (float) $t->harga_per_kg,
                    'estimasi_hari' => (int) $t->estimasi_hari,
                    'status' => $t->status,
                ];
            })
            ->values();

        return Inertia::render('Settings/Index', [
            'activeTab' => $tab,
            'kotaList' => $kotaList,
            'tarifList' => $tarifList,
        ]);
    }
}
