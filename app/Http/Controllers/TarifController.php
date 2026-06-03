<?php

namespace App\Http\Controllers;

use App\Models\Kota;
use App\Services\TarifService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class TarifController extends Controller
{
    public function index(): Response
    {
        // Cache data kota aktif selama 1 jam (3600 detik) untuk efisiensi
        $kota = Cache::remember('kota_aktif_list', 3600, function () {
            return Kota::query()
                ->aktif()
                ->orderBy('nama_kota')
                ->get(['id', 'nama_kota', 'provinsi', 'kode_pos']);
        });

        return Inertia::render('Tarif/Index', [
            'kota' => $kota,
            'kotaList' => $kota,
        ]);
    }

    public function cek(Request $request, TarifService $tarifService): JsonResponse
    {
        $validated = $request->validate(
            [
                'kota_asal_id' => ['required', 'exists:kota,id'],
                'kota_tujuan_id' => ['required', 'exists:kota,id'],
                'berat_kg' => ['required', 'numeric', 'min:0.01'],
                'volume_cm3' => ['nullable', 'numeric', 'min:0'],
            ],
            [
                'kota_asal_id.required' => 'Kota asal wajib dipilih.',
                'kota_asal_id.exists' => 'Kota asal tidak valid.',
                'kota_tujuan_id.required' => 'Kota tujuan wajib dipilih.',
                'kota_tujuan_id.exists' => 'Kota tujuan tidak valid.',
                'berat_kg.required' => 'Berat wajib diisi.',
                'berat_kg.numeric' => 'Berat harus berupa angka.',
                'berat_kg.min' => 'Berat minimal 0.01 kg.',
                'volume_cm3.numeric' => 'Volume harus berupa angka.',
                'volume_cm3.min' => 'Volume tidak boleh negatif.',
            ]
        );

        $hasil = $tarifService->hitung(
            (int) $validated['kota_asal_id'],
            (int) $validated['kota_tujuan_id'],
            (float) $validated['berat_kg'],
            isset($validated['volume_cm3']) ? (float) $validated['volume_cm3'] : null
        );

        return response()->json([
            'data' => $hasil,
        ]);
    }
}
