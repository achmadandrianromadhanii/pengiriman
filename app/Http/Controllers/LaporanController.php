<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class LaporanController extends Controller
{
    private function resolveRange(Request $request): array
    {
        $periode = $request->string('periode', 'bulan_ini')->toString();
        $dari = $request->string('dari')->toString();
        $sampai = $request->string('sampai')->toString();

        $now = now();

        $start = $now->copy()->startOfMonth()->startOfDay();
        $end = $now->copy()->endOfMonth()->endOfDay();

        if ($periode === 'hari_ini') {
            $start = $now->copy()->startOfDay();
            $end = $now->copy()->endOfDay();
        } elseif ($periode === 'minggu_ini') {
            $start = $now->copy()->startOfWeek()->startOfDay();
            $end = $now->copy()->endOfWeek()->endOfDay();
        } elseif ($periode === 'bulan_ini') {
            $start = $now->copy()->startOfMonth()->startOfDay();
            $end = $now->copy()->endOfMonth()->endOfDay();
        } elseif ($periode === 'bulan_lalu') {
            $start = $now->copy()->subMonthNoOverflow()->startOfMonth()->startOfDay();
            $end = $now->copy()->subMonthNoOverflow()->endOfMonth()->endOfDay();
        } elseif ($periode === 'tahun_ini') {
            $start = $now->copy()->startOfYear()->startOfDay();
            $end = $now->copy()->endOfYear()->endOfDay();
        } elseif ($periode === 'custom' && $dari !== '' && $sampai !== '') {
            try {
                $start = Carbon::parse($dari)->startOfDay();
                $end = Carbon::parse($sampai)->endOfDay();
            } catch (\Throwable) {
                $periode = 'bulan_ini';
                $start = $now->copy()->startOfMonth()->startOfDay();
                $end = $now->copy()->endOfMonth()->endOfDay();
            }
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        return [$periode, $start, $end];
    }

    // Fungsi untuk mendapatkan query dasar laporan yang sudah difilter
    private function getBaseQuery(Request $request, Carbon $start, Carbon $end)
    {
        $query = Pengiriman::query()->whereBetween('created_at', [$start, $end]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_resi', 'like', "%{$search}%")
                    ->orWhere('pengirim_nama', 'like', "%{$search}%")
                    ->orWhere('penerima_nama', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function index(Request $request): Response
    {
        [$periode, $start, $end] = $this->resolveRange($request);

        $baseQuery = $this->getBaseQuery($request, $start, $end);

        // UPDATE: Menghitung ringkasan KPI (summary) dari seluruh data pada periode ini.
        // Fungsi: Memberikan gambaran besar (total pengiriman, pendapatan, sukses, gagal)
        //         di atas tabel laporan agar admin langsung paham kondisi bisnis.
        // Cara Kerja: Query aggregate (count, sum) dijalankan sekali di server,
        //             sangat ringan dan tidak membebani memori.
        $summary = $this->buildSummary(clone $baseQuery);

        // Data Tabel Berpaginasi Server-Side (Cepat, Ringan, Aman Memori)
        $pengiriman = (clone $baseQuery)
            ->with(['pengirimKota:id,nama_kota', 'penerimaKota:id,nama_kota'])
            ->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 15))
            ->withQueryString()
            ->through(function ($p) {
                return [
                    'id' => $p->id,
                    'nomor_resi' => $p->nomor_resi,
                    'pengirim_nama' => $p->pengirim_nama,
                    'penerima_nama' => $p->penerima_nama,
                    'asal_kota' => $p->pengirimKota?->nama_kota,
                    'tujuan_kota' => $p->penerimaKota?->nama_kota,
                    'jenis_layanan' => $p->jenis_layanan,
                    'status' => $p->status,
                    'total_biaya' => (float) $p->total_biaya,
                    'estimasi_tiba' => $p->estimasi_tiba ? Carbon::parse($p->estimasi_tiba)->toDateString() : null,
                    'tanggal_terkirim' => $p->tanggal_terkirim ? Carbon::parse($p->tanggal_terkirim)->toDateTimeString() : null,
                    'created_at' => $p->created_at ? $p->created_at->toDateTimeString() : null,
                ];
            });

        return Inertia::render('Laporan/Index', [
            'filters' => [
                'periode' => $periode,
                'dari' => $start->toDateString(),
                'sampai' => $end->toDateString(),
                'search' => $request->search ?? '',
                'per_page' => (int) $request->input('per_page', 15),
            ],
            'summary' => $summary,
            'pengiriman' => $pengiriman,
        ]);
    }

    public function pdf(Request $request)
    {
        [$periode, $start, $end] = $this->resolveRange($request);

        $baseQuery = $this->getBaseQuery($request, $start, $end);

        // UPDATE: Mengirim data summary ke PDF agar sinkron dengan halaman web
        $summary = $this->buildSummary(clone $baseQuery);

        $pengiriman = (clone $baseQuery)
            ->with(['pengirimKota:id,nama_kota', 'penerimaKota:id,nama_kota'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($p) {
                return [
                    'nomor_resi' => $p->nomor_resi,
                    'pengirim_nama' => $p->pengirim_nama,
                    'asal_kota' => $p->pengirimKota?->nama_kota,
                    'penerima_nama' => $p->penerima_nama,
                    'tujuan_kota' => $p->penerimaKota?->nama_kota,
                    'jenis_layanan' => $p->jenis_layanan,
                    'status' => $p->status,
                    'total_biaya' => (float) $p->total_biaya,
                    'created_at' => $p->created_at ? $p->created_at->format('d M Y H:i') : null,
                ];
            })->toArray();

        $payload = [
            'filters' => [
                'periode' => $periode,
                'dari' => $start->toDateString(),
                'sampai' => $end->toDateString(),
            ],
            'summary' => $summary,
            'pengiriman' => $pengiriman,
            'printedAt' => now(),
        ];

        $filename = 'Laporan_SuperStart_'.$periode.'_'.$start->format('Ymd').'-'.$end->format('Ymd').'.pdf';

        // UPDATE: Ekspor dikonversi menjadi mode Landscape (parameter 'L')
        // Fungsi: Mencegah tabel yang kompleks agar tidak berantakan/melipat (wrap)
        // Cara Kerja: 'L' memberi instruksi pada TCPDF untuk melebarkan dimensi cetak
        // dari 210mm menjadi 297mm secara fisik, sehingga sama persis & rapi seperti di website.
        return app(PdfService::class)->download('pdf.laporan', $payload, $filename, 'L');
    }

    /**
     * UPDATE: Fungsi pembantu untuk menghitung ringkasan KPI laporan.
     * Fungsi: Menghitung 4 metrik utama (Total Pengiriman, Pendapatan, Sukses, Gagal/Batal)
     *         dari query yang sudah difilter berdasarkan periode dan pencarian.
     * Cara Kerja: Menggunakan aggregate query (count, sum, conditional count)
     *             yang sangat ringan karena diproses langsung di level database.
     * Letak: Dipanggil oleh method index() dan pdf() agar data sinkron.
     */
    private function buildSummary($query): array
    {
        $total = (clone $query)->count();
        $pendapatan = (float) (clone $query)->sum('total_biaya');
        $terkirim = (clone $query)->where('status', 'terkirim')->count();
        $gagalBatal = (clone $query)->whereIn('status', ['gagal', 'dibatalkan'])->count();

        return [
            'totalPengiriman' => $total,
            'totalPendapatan' => $pendapatan,
            'totalTerkirim' => $terkirim,
            'totalGagalBatal' => $gagalBatal,
        ];
    }
}
