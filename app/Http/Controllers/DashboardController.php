<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        Carbon::setLocale('id');

        $data = Cache::remember('dashboard.stats', now()->addSeconds(30), function (): array {
            // ── Stat utama ──────────────────────────────────────────────────
            $totalPengiriman = Pengiriman::count();

            $totalPendapatan = (float) Pengiriman::where('status', 'terkirim')->sum('total_biaya');

            // ── Bulan ini vs bulan lalu (untuk badge delta) ─────────────────
            $startThisMonth = now()->startOfMonth();
            $endThisMonth = now()->endOfMonth();

            $startLastMonth = now()->subMonthNoOverflow()->startOfMonth();
            $endLastMonth = now()->subMonthNoOverflow()->endOfMonth();

            $pengirimanThisMonth = Pengiriman::whereBetween('created_at', [$startThisMonth, $endThisMonth])->count();
            $pengirimanLastMonth = Pengiriman::whereBetween('created_at', [$startLastMonth, $endLastMonth])->count();
            $pengirimanDelta = $pengirimanThisMonth - $pengirimanLastMonth;

            $pendapatanThisMonth = (float) Pengiriman::where('status', 'terkirim')
                ->whereBetween('created_at', [$startThisMonth, $endThisMonth])
                ->sum('total_biaya');

            $pendapatanLastMonth = (float) Pengiriman::where('status', 'terkirim')
                ->whereBetween('created_at', [$startLastMonth, $endLastMonth])
                ->sum('total_biaya');

            $pendapatanDeltaPercent = 0.0;
            if ($pendapatanLastMonth > 0) {
                $pendapatanDeltaPercent = (($pendapatanThisMonth - $pendapatanLastMonth) / $pendapatanLastMonth) * 100;
            } elseif ($pendapatanThisMonth > 0) {
                $pendapatanDeltaPercent = 100.0;
            }

            // ── 4 KPI Utama ─────────────────────────────────────────────────
            $diproses = Pengiriman::whereIn('status', ['pending', 'diproses', 'dalam_perjalanan', 'sedang_diantar', 'tiba_di_kota_tujuan'])->count();
            $gagalBatal = Pengiriman::whereIn('status', ['gagal', 'dibatalkan'])->count();
            $terkirim = Pengiriman::where('status', 'terkirim')->count();
            $terlambat = Pengiriman::terlambat()->count();

            $totalSelesai = $terkirim + $gagalBatal;
            $successRatePercent = $totalSelesai > 0 ? round(($terkirim / $totalSelesai) * 100, 1) : 0;

            // ── Chart 12 bulan terakhir (Area Spline) ───────────────────────
            $start = now()->startOfMonth()->subMonths(11);
            $end = now()->endOfMonth();

            // ── [UPDATE: KOMPATIBILITAS DATABASE (POSTGRESQL & MYSQL)] ─────────────
            // Fungsi: Mendeteksi jenis database yang sedang digunakan untuk query tanggal
            // Penjelasan: MySQL menggunakan DATE_FORMAT, sedangkan PostgreSQL menggunakan TO_CHAR.
            // Kode ini membuat sistem pintar mendeteksi driver sehingga aman dari error "Undefined column"
            // baik di server lokal (MySQL/PgSQL) maupun di Vercel Neon (PostgreSQL).
            $dbDriver = DB::connection()->getDriverName();
            if ($dbDriver === 'pgsql') {
                $dateFormatRaw = "TO_CHAR(created_at, 'YYYY-MM')";
            } elseif ($dbDriver === 'sqlite') {
                // SQLite digunakan saat proses Testing (PHPUnit) in-memory
                $dateFormatRaw = "strftime('%Y-%m', created_at)";
            } else {
                $dateFormatRaw = "DATE_FORMAT(created_at, '%Y-%m')";
            }

            $shipmentsByMonth = Pengiriman::selectRaw("$dateFormatRaw as ym, COUNT(*) as total")
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('ym')
                ->pluck('total', 'ym')
                ->toArray();

            $revenueByMonth = Pengiriman::selectRaw("$dateFormatRaw as ym, COALESCE(SUM(total_biaya), 0) as total")
                ->where('status', 'terkirim')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('ym')
                ->pluck('total', 'ym')
                ->toArray();

            $labels = [];
            $seriesS = [];
            $seriesR = [];

            $cursor = $start->copy();
            for ($i = 0; $i < 12; $i++) {
                $ym = $cursor->format('Y-m');
                $labels[] = $cursor->translatedFormat('M Y');
                $seriesS[] = (int) ($shipmentsByMonth[$ym] ?? 0);
                $seriesR[] = (float) ($revenueByMonth[$ym] ?? 0);
                $cursor->addMonth();
            }

            // ── Custom Radial Bar (Distribusi Status Real-Time) ─────────────
            // Kita kelompokkan: Terkirim, Sedang Berjalan (Diproses+Diantar), Gagal/Batal
            $statusRadial = [
                'Terkirim' => $terkirim,
                'Sedang Berjalan' => $diproses,
                'Kendala/Batal' => $gagalBatal,
            ];

            // ── Hollow Donut Chart (Analitik Layanan) ───────────────────────
            $layananOrder = ['reguler', 'express', 'kargo', 'ekonomi'];
            $layananCounts = Pengiriman::selectRaw('jenis_layanan, COUNT(*) as total')
                ->groupBy('jenis_layanan')
                ->pluck('total', 'jenis_layanan')
                ->toArray();

            $layananLabels = collect($layananOrder)->map(fn ($l) => $this->layananLabel($l))->values()->all();
            $layananValues = collect($layananOrder)->map(fn ($l) => (int) ($layananCounts[$l] ?? 0))->values()->all();

            // ── Bar Horizontal (Top 5 Kota Tujuan) ──────────────────────────
            $topKota = Pengiriman::selectRaw('penerima_kota_id, COUNT(*) as total')
                ->with('penerimaKota:id,nama_kota')
                ->groupBy('penerima_kota_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            // Penjelasan: Menggunakan method getRelationValue() memastikan PHPStan mengerti bahwa kita mengakses relasi 'penerimaKota', menghindari error undefined property.
            $topKotaLabels = $topKota->map(fn ($k) => $k->getRelationValue('penerimaKota')->nama_kota ?? 'Unknown')->toArray();

            // Penjelasan: Menggunakan getAttribute('total') agar aman dari error property undefined di PHPStan, karena 'total' adalah hasil query agregasi (COUNT), bukan kolom tabel asli.
            $topKotaValues = $topKota->map(fn ($k) => (int) $k->getAttribute('total'))->toArray();

            $topKotaList = [];
            foreach ($topKota as $k) {
                $nama = $k->getRelationValue('penerimaKota')->nama_kota ?? 'Unknown';
                $val = (int) $k->getAttribute('total');
                if ($val > 0) {
                    $topKotaList[] = [
                        'name' => $nama,
                        'value' => $val,
                    ];
                }
            }

            // [UPDATE: PADDING DATA UNTUK SPLINE AREA CHART]
            // Fungsi: Menambahkan titik data kosong (0) agar array selalu berjumlah 5.
            while (count($topKotaLabels) < 5) {
                $topKotaLabels[] = ''; // Label kosong agar sumbu X tetap bersih
                $topKotaValues[] = 0;
            }

            $dalamPerjalananCount = Pengiriman::whereIn('status', ['dalam_perjalanan', 'tiba_di_kota_tujuan', 'sedang_diantar'])->count();
            $diprosesOnlyCount = Pengiriman::whereIn('status', ['pending', 'diproses'])->count();

            // Hitung delta nyata bulan ini vs bulan lalu (100% data database murni)
            $dalamProsesThisMonth = Pengiriman::whereIn('status', ['pending', 'diproses', 'dalam_perjalanan', 'sedang_diantar', 'tiba_di_kota_tujuan'])
                ->whereBetween('created_at', [$startThisMonth, $endThisMonth])->count();
            $dalamProsesLastMonth = Pengiriman::whereIn('status', ['pending', 'diproses', 'dalam_perjalanan', 'sedang_diantar', 'tiba_di_kota_tujuan'])
                ->whereBetween('created_at', [$startLastMonth, $endLastMonth])->count();
            $dalamProsesDeltaPercent = $dalamProsesLastMonth > 0
                ? round((($dalamProsesThisMonth - $dalamProsesLastMonth) / $dalamProsesLastMonth) * 100, 1)
                : ($dalamProsesThisMonth > 0 ? 100.0 : 0.0);

            $terkirimThisMonth = Pengiriman::where('status', 'terkirim')->whereBetween('created_at', [$startThisMonth, $endThisMonth])->count();
            $terkirimLastMonth = Pengiriman::where('status', 'terkirim')->whereBetween('created_at', [$startLastMonth, $endLastMonth])->count();
            $terkirimDeltaPercent = $terkirimLastMonth > 0
                ? round((($terkirimThisMonth - $terkirimLastMonth) / $terkirimLastMonth) * 100, 1)
                : ($terkirimThisMonth > 0 ? 100.0 : 0.0);

            $bermasalahTotal = $terlambat + $gagalBatal;
            $bermasalahThisMonth = Pengiriman::where(function ($q) {
                $q->whereIn('status', ['gagal', 'dibatalkan'])
                    ->orWhere(function ($sub) {
                        $sub->whereNotIn('status', ['terkirim', 'gagal', 'dibatalkan'])
                            ->where('estimasi_tiba', '<', today());
                    });
            })->whereBetween('created_at', [$startThisMonth, $endThisMonth])->count();

            $bermasalahLastMonth = Pengiriman::where(function ($q) {
                $q->whereIn('status', ['gagal', 'dibatalkan'])
                    ->orWhere(function ($sub) {
                        $sub->whereNotIn('status', ['terkirim', 'gagal', 'dibatalkan'])
                            ->where('estimasi_tiba', '<', today());
                    });
            })->whereBetween('created_at', [$startLastMonth, $endLastMonth])->count();

            $bermasalahDeltaPercent = $bermasalahLastMonth > 0
                ? round((($bermasalahThisMonth - $bermasalahLastMonth) / $bermasalahLastMonth) * 100, 1)
                : ($bermasalahThisMonth > 0 ? 100.0 : 0.0);

            $selesaiLastMonth = $terkirimLastMonth + $bermasalahLastMonth;
            $successRateLastMonth = $selesaiLastMonth > 0 ? round(($terkirimLastMonth / $selesaiLastMonth) * 100, 1) : 0.0;
            $successRateDeltaPercent = round($successRatePercent - $successRateLastMonth, 1);

            // 6 bulan terakhir untuk desktop chart
            $cursor6 = now()->startOfMonth()->subMonths(5);
            $labels6 = [];
            $seriesS6 = [];
            $seriesR6 = [];
            for ($i = 0; $i < 6; $i++) {
                $ym = $cursor6->format('Y-m');
                $labels6[] = $cursor6->translatedFormat('M Y');
                $seriesS6[] = (int) ($shipmentsByMonth[$ym] ?? 0);
                $seriesR6[] = (float) ($revenueByMonth[$ym] ?? 0);
                $cursor6->addMonth();
            }

            return [
                'stats' => [
                    'totalPengiriman' => $totalPengiriman,
                    'totalPendapatan' => $totalPendapatan,
                    'pengirimanDelta' => $pengirimanDelta,
                    'pengirimanDeltaPercent' => $pengirimanLastMonth > 0 ? round((($pengirimanThisMonth - $pengirimanLastMonth) / $pengirimanLastMonth) * 100, 1) : ($pengirimanThisMonth > 0 ? 100.0 : 0.0),
                    'pendapatanDeltaPercent' => $pendapatanDeltaPercent,
                    'successRate' => $successRatePercent,
                    'successRateDeltaPercent' => $successRateDeltaPercent,
                    'paketAktif' => $diproses,
                    'dalamProses' => $diproses,
                    'dalamProsesDeltaPercent' => $dalamProsesDeltaPercent,
                    'paketTerkirim' => $terkirim,
                    'terkirimDeltaPercent' => $terkirimDeltaPercent,
                    'paketBermasalah' => $bermasalahTotal,
                    'bermasalahDeltaPercent' => $bermasalahDeltaPercent,
                ],
                'charts' => [
                    'area' => [
                        'labels' => $labels,
                        'seriesRevenue' => $seriesR,
                        'seriesShipments' => $seriesS,
                        'labels6' => $labels6,
                        'seriesRevenue6' => $seriesR6,
                        'seriesShipments6' => $seriesS6,
                    ],
                    'status' => [
                        'terkirim' => $terkirim,
                        'dalam_perjalanan' => $dalamPerjalananCount,
                        'diproses' => $diprosesOnlyCount,
                        'bermasalah' => $bermasalahTotal,
                    ],
                    'layanan' => [
                        'reguler' => (int) ($layananCounts['reguler'] ?? 0),
                        'express' => (int) ($layananCounts['express'] ?? 0),
                        'ekonomi' => (int) ($layananCounts['ekonomi'] ?? 0),
                        'kargo' => (int) ($layananCounts['kargo'] ?? 0),
                    ],
                    'topKotaList' => $topKotaList,
                    'topKota' => [
                        'labels' => $topKotaLabels,
                        'series' => $topKotaValues,
                    ],
                    'radial' => [
                        'labels' => array_keys($statusRadial),
                        'series' => array_values($statusRadial),
                    ],
                    'donut' => [
                        'labels' => $layananLabels,
                        'series' => $layananValues,
                    ],
                    'bar' => [
                        'labels' => $topKotaLabels,
                        'series' => $topKotaValues,
                    ],
                ],
            ];
        });

        // ── 5 pengiriman terbaru (dengan Paginasi) ──────────────────────────────
        // Diletakkan DI LUAR cache agar Paginasi (Next/Prev) merespon parameter `?page=` secara dinamis.
        $data['latest'] = Pengiriman::query()
            ->select(['id', 'nomor_resi', 'pengirim_nama', 'penerima_kota_id', 'jenis_layanan', 'status', 'created_at', 'estimasi_tiba'])
            ->with(['penerimaKota:id,nama_kota'])
            ->orderByDesc('created_at')
            ->paginate(5)
            ->through(function ($p): array {
                /**
                 * @var Pengiriman $p
                 *                 Penjelasan: Memberitahu PHPStan bahwa variabel $p adalah instance dari model Pengiriman.
                 */

                // Penjelasan: Menggunakan ->lt(today()) langsung karena kolom estimasi_tiba sudah di-cast menjadi Carbon instance di Model, sehingga PHPStan tahu ini bukan null string.
                $isTerlambat = $p->estimasi_tiba->lt(today()) && ! in_array($p->status, ['terkirim', 'dibatalkan', 'gagal'], true);

                return [
                    'id' => $p->id,
                    'nomor_resi' => $p->nomor_resi,
                    'pengirim_nama' => $p->pengirim_nama,
                    // Penjelasan: Sama seperti di atas, getRelationValue digunakan agar relasi terbaca valid oleh PHPStan.
                    'tujuan_kota' => $p->getRelationValue('penerimaKota')->nama_kota ?? null,
                    'jenis_layanan' => $p->jenis_layanan,
                    'status' => $p->status,
                    // Penjelasan: created_at dipastikan sebagai objek Carbon, sehingga toISOString() aman dipanggil langsung.
                    'created_at' => $p->created_at->toISOString(),
                    'is_terlambat' => $isTerlambat,
                ];
            });

        return Inertia::render('Dashboard/Index', $data);
    }

    private function layananLabel(string $jenis): string
    {
        return match ($jenis) {
            'reguler' => 'Reguler',
            'express' => 'Express',
            'kargo' => 'Kargo',
            'ekonomi' => 'Ekonomi',
            default => $jenis,
        };
    }
}
