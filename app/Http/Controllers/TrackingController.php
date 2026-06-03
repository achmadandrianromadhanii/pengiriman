<?php

namespace App\Http\Controllers;

use App\Events\PengirimanUpdated;
use App\Models\Pengiriman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    public function search(Request $request): Response|RedirectResponse
    {
        $resi = trim((string) $request->query('resi', ''));

        if ($resi !== '') {
            return redirect()->route('tracking.public', $resi);
        }

        return Inertia::render('Tracking/Search');
    }

    public function public(string $nomor_resi): Response|RedirectResponse
    {
        $nomor_resi = trim($nomor_resi);

        $pengiriman = Pengiriman::query()
            ->select([
                'id',
                'nomor_resi',
                'status',
                'estimasi_tiba',
                'pengirim_nama',
                'penerima_nama',
                'pengirim_kota_id',
                'penerima_kota_id',
            ])
            ->where('nomor_resi', $nomor_resi)
            ->with([
                'pengirimKota:id,nama_kota,provinsi,kode_pos,latitude,longitude',
                'penerimaKota:id,nama_kota,provinsi,kode_pos,latitude,longitude',
                'trackingHistories:id,pengiriman_id,status_lama,status_baru,lokasi,keterangan,created_at',
            ])
            ->first();

        if (! $pengiriman) {
            return redirect()
                ->route('tracking.search')
                ->with('error', 'Resi tidak ditemukan');
        }

        return Inertia::render('Tracking/Public', [
            'pengiriman' => $pengiriman,
        ]);
    }

    public function update(Request $request, Pengiriman $pengiriman): RedirectResponse
    {
        // VALIDASI LANGSUNG SAJA: Hapus kewajiban mengisi lokasi yang mendetail dan hapus kolom keterangan.
        // Ini memastikan Admin bisa bekerja ekstra cepat hanya dengan 2 kali klik (Pilih Status -> Simpan).
        $validated = $request->validate(
            [
                'status_baru' => 'required|in:pending,diproses,dalam_perjalanan,tiba_di_kota_tujuan,sedang_diantar,terkirim,gagal,dibatalkan',
                'lokasi' => 'nullable|string|max:200', // Lokasi kini opsional
            ],
            [
                'status_baru.required' => 'Status baru wajib dipilih.',
                'status_baru.in' => 'Status baru tidak valid.',
                'lokasi.max' => 'Lokasi maksimal 200 karakter.',
            ]
        );

        $adminId = (int) Auth::guard('web')->id();

        DB::transaction(function () use ($pengiriman, $validated, $adminId): void {
            $statusLama = (string) $pengiriman->status;

            $updates = [
                'status' => $validated['status_baru'],
            ];

            if ($validated['status_baru'] === 'terkirim') {
                $updates['tanggal_terkirim'] = now();
            }

            $pengiriman->forceFill($updates)->save();

            // Simpan riwayat tanpa kolom keterangan
            $pengiriman->trackingHistories()->create([
                'status_lama' => $statusLama,
                'status_baru' => $validated['status_baru'],
                'lokasi' => $validated['lokasi'] ?? 'Sistem', // Default 'Sistem' jika lokasi dikosongkan
                'keterangan' => '-', // Kolom keterangan di DB diisi strip karena fitur dicabut untuk mempercepat kinerja Admin
                'admin_id' => $adminId,
            ]);
        });

        event(new PengirimanUpdated($pengiriman->nomor_resi, $validated['status_baru'], $validated['lokasi'] ?? 'Sistem'));

        return back()->with('success', 'Status pengiriman berhasil diperbarui.');
    }
}
