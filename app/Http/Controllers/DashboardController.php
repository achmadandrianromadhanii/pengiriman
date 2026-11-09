<?php

namespace App\Http\Controllers;

use App\Models\Pengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        Carbon::setLocale('id');

        $period = (string) $request->query('period', 'bulan_ini');
        $layanan = (string) $request->query('layanan', 'semua');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $now = now();

        switch ($period) {
            case 'hari_ini':
                $currentStart = $now->copy()->startOfDay();
                $currentEnd = $now->copy()->endOfDay();
                $prevStart = $now->copy()->subDay()->startOfDay();
                $prevEnd = $now->copy()->subDay()->endOfDay();
                $deltaLabel = 'vs kemarin';
                $periodLabel = 'Hari Ini';
                break;

            case '7_hari':
                $currentStart = $now->copy()->subDays(6)->startOfDay();
                $currentEnd = $now->copy()->endOfDay();
                $prevStart = $now->copy()->subDays(13)->startOfDay();
                $prevEnd = $now->copy()->subDays(7)->endOfDay();
                $deltaLabel = 'vs 7 hari lalu';
                $periodLabel = '7 Hari Terakhir';
                break;

            case '30_hari':
                $currentStart = $now->copy()->subDays(29)->startOfDay();
                $currentEnd = $now->copy()->endOfDay();
                $prevStart = $now->copy()->subDays(59)->startOfDay();
                $prevEnd = $now->copy()->subDays(30)->endOfDay();
                $deltaLabel = 'vs 30 hari lalu';
                $periodLabel = '30 Hari Terakhir';
                break;

            case 'bulan_lalu':
                $currentStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
                $currentEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
                $prevStart = $now->copy()->subMonthsNoOverflow(2)->startOfMonth();
                $prevEnd = $now->copy()->subMonthsNoOverflow(2)->endOfMonth();
                $deltaLabel = 'vs bulan sebelumnya';
                $periodLabel = 'Bulan Lalu';
                break;

            case 'tahun_ini':
                $currentStart = $now->copy()->startOfYear();
                $currentEnd = $now->copy()->endOfYear();
                $prevStart = $now->copy()->subYear()->startOfYear();
                $prevEnd = $now->copy()->subYear()->endOfYear();
                $deltaLabel = 'vs tahun lalu';
                $periodLabel = 'Tahun Ini (' . $now->year . ')';
                break;

            case 'custom':
                $currentStart = $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->startOfMonth();
                $currentEnd = $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfDay();
                $diffDays = max(1, (int) $currentStart->diffInDays($currentEnd));
                $prevEnd = $currentStart->copy()->subSecond();
                $prevStart = $prevEnd->copy()->subDays($diffDays)->startOfDay();
                $deltaLabel = 'vs periode lalu';
                $periodLabel = $currentStart->format('d/m/Y') . ' - ' . $currentEnd->format('d/m/Y');
                break;

            case 'bulan_ini':
            default:
                $period = 'bulan_ini';
                $currentStart = $now->copy()->startOfMonth();
                $currentEnd = $now->copy()->endOfMonth();
                $prevStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
                $prevEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();
                $deltaLabel = 'vs bulan lalu';
                $periodLabel = 'Bulan Ini';
                break;
        }

        $cacheKey = 'dashboard.stats.' . md5(json_encode([
            'p' => $period,
            's' => $currentStart->toDateTimeString(),
            'e' => $currentEnd->toDateTimeString(),
            'l' => $layanan,
        ]));

        $data = Cache::remember($cacheKey, now()->addSeconds(10), function () use (
            $currentStart,
            $currentEnd,
            $prevStart,
            $prevEnd,
            $period,
            $periodLabel,
            $deltaLabel,
            $layanan
        ): array {
            // ── Helper filter layanan ───────────────────────────────────────
            $filterLayanan = function ($query) use ($layanan) {
                if ($layanan && $layanan !== 'semua') {
                    $query->where('jenis_layanan', $layanan);
                }
            };

            // ── 1. Stat Utama & Perhitungan Delta ───────────────────────────
            $totalPengiriman = Pengiriman::whereBetween('created_at', [$currentStart, $currentEnd])
                ->tap($filterLayanan)
                ->count();

            $prevTotalPengiriman = Pengiriman::whereBetween('created_at', [$prevStart, $prevEnd])
                ->tap($filterLayanan)
                ->count();

            $pengirimanDelta = $totalPengiriman - $prevTotalPengiriman;
            $pengirimanDeltaPercent = $prevTotalPengiriman > 0
                ? round((($totalPengiriman - $prevTotalPengiriman) / $prevTotalPengiriman) * 100, 1)
                : ($totalPengiriman > 0 ? 100.0 : 0.0);

            $totalPendapatan = (float) Pengiriman::where('status', 'terkirim')
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->tap($filterLayanan)
                ->sum('total_biaya');

            $prevTotalPendapatan = (float) Pengiriman::where('status', 'terkirim')
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->tap($filterLayanan)
                ->sum('total_biaya');

            $pendapatanDeltaPercent = $prevTotalPendapatan > 0
                ? round((($totalPendapatan - $prevTotalPendapatan) / $prevTotalPendapatan) * 100, 1)
                : ($totalPendapatan > 0 ? 100.0 : 0.0);

            // ── 2. Empat KPI Status Pengiriman ──────────────────────────────
            // Dalam proses: pending, diproses, dalam_perjalanan, sedang_diantar, tiba_di_kota_tujuan
            $dalamProses = Pengiriman::whereIn('status', ['pending', 'diproses', 'dalam_perjalanan', 'sedang_diantar', 'tiba_di_kota_tujuan'])
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->tap($filterLayanan)
                ->count();

            $prevDalamProses = Pengiriman::whereIn('status', ['pending', 'diproses', 'dalam_perjalanan', 'sedang_diantar', 'tiba_di_kota_tujuan'])
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->tap($filterLayanan)
                ->count();

            $dalamProsesDeltaPercent = $prevDalamProses > 0
                ? round((($dalamProses - $prevDalamProses) / $prevDalamProses) * 100, 1)
                : ($dalamProses > 0 ? 100.0 : 0.0);

            // Paket terkirim
            $terkirim = Pengiriman::where('status', 'terkirim')
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->tap($filterLayanan)
                ->count();

            $prevTerkirim = Pengiriman::where('status', 'terkirim')
                ->whereBetween('created_at', [$prevStart, $prevEnd])
                ->tap($filterLayanan)
                ->count();

            $terkirimDeltaPercent = $prevTerkirim > 0
                ? round((($terkirim - $prevTerkirim) / $prevTerkirim) * 100, 1)
                : ($terkirim > 0 ? 100.0 : 0.0);

            // Paket bermasalah: gagal, batal, atau lewat estimasi
            $queryBermasalah = function ($q, $start, $end) use ($filterLayanan) {
                $q->where(function ($sub) {
                    $sub->whereIn('status', ['gagal', 'dibatalkan'])
                        ->orWhere(function ($s) {
                            $s->whereNotIn('status', ['terkirim', 'gagal', 'dibatalkan'])
                              ->where('estimasi_tiba', '<', today());
                        });
                })
                ->whereBetween('created_at', [$start, $end])
                ->tap($filterLayanan);
            };

            $paketBermasalah = Pengiriman::where(function ($q) use ($queryBermasalah, $currentStart, $currentEnd) {
                $queryBermasalah($q, $currentStart, $currentEnd);
            })->count();

            $prevPaketBermasalah = Pengiriman::where(function ($q) use ($queryBermasalah, $prevStart, $prevEnd) {
                $queryBermasalah($q, $prevStart, $prevEnd);
            })->count();

            $bermasalahDeltaPercent = $prevPaketBermasalah > 0
                ? round((($paketBermasalah - $prevPaketBermasalah) / $prevPaketBermasalah) * 100, 1)
                : ($paketBermasalah > 0 ? 100.0 : 0.0);

            // Tingkat Keberhasilan
            $totalSelesai = $terkirim + $paketBermasalah;
            $successRatePercent = $totalSelesai > 0 ? round(($terkirim / $totalSelesai) * 100, 1) : 0.0;

            $prevTotalSelesai = $prevTerkirim + $prevPaketBermasalah;
            $prevSuccessRatePercent = $prevTotalSelesai > 0 ? round(($prevTerkirim / $prevTotalSelesai) * 100, 1) : 0.0;
            $successRateDeltaPercent = round($successRatePercent - $prevSuccessRatePercent, 1);

            // ── 3. Breakdown Status untuk Donut Chart ────────────────────────
            $dalamPerjalananCount = Pengiriman::whereIn('status', ['dalam_perjalanan', 'tiba_di_kota_tujuan', 'sedang_diantar'])
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->tap($filterLayanan)
                ->count();

            $diprosesOnlyCount = Pengiriman::whereIn('status', ['pending', 'diproses'])
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->tap($filterLayanan)
                ->count();

            // ── 4. Distribusi Layanan ────────────────────────────────────────
            $layananOrder = ['reguler', 'express', 'ekonomi', 'kargo'];
            $layananCounts = Pengiriman::selectRaw('jenis_layanan, COUNT(*) as total')
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->groupBy('jenis_layanan')
                ->pluck('total', 'jenis_layanan')
                ->toArray();

            // ── 5. Top 5 Kota Tujuan ────────────────────────────────────────
            $topKota = Pengiriman::selectRaw('penerima_kota_id, COUNT(*) as total')
                ->with('penerimaKota:id,nama_kota')
                ->whereBetween('created_at', [$currentStart, $currentEnd])
                ->tap($filterLayanan)
                ->groupBy('penerima_kota_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $topKotaList = [];
            foreach ($topKota as $k) {
                $nama = $k->getRelationValue('penerimaKota')->nama_kota ?? 'Lainnya';
                $val = (int) $k->getAttribute('total');
                if ($val > 0) {
                    $topKotaList[] = [
                        'name' => $nama,
                        'value' => $val,
                    ];
                }
            }

            // ── 6. Grafik Tren Sesuai Periode (Adaptif & Dinamis) ────────────
            $labelsTren = [];
            $seriesShipments = [];
            $seriesRevenue = [];

            if ($period === 'hari_ini') {
                // Hari ini: 6 interval waktu (tiap 4 jam)
                $hours = [
                    ['00:00', '03:59', '00:00 - 04:00'],
                    ['04:00', '07:59', '04:00 - 08:00'],
                    ['08:00', '11:59', '08:00 - 12:00'],
                    ['12:00', '15:59', '12:00 - 16:00'],
                    ['16:00', '19:59', '16:00 - 20:00'],
                    ['20:00', '23:59', '20:00 - 24:00'],
                ];
                $todayDate = $now->format('Y-m-d');
                foreach ($hours as $h) {
                    $slotStart = Carbon::parse("$todayDate {$h[0]}:00");
                    $slotEnd = Carbon::parse("$todayDate {$h[1]}:59");
                    $labelsTren[] = $h[2];
                    $seriesShipments[] = Pengiriman::whereBetween('created_at', [$slotStart, $slotEnd])
                        ->tap($filterLayanan)
                        ->count();
                    $seriesRevenue[] = (float) Pengiriman::where('status', 'terkirim')
                        ->whereBetween('created_at', [$slotStart, $slotEnd])
                        ->tap($filterLayanan)
                        ->sum('total_biaya');
                }
            } elseif ($period === '7_hari') {
                // 7 Hari terakhir: per hari
                $cursorDay = $currentStart->copy();
                for ($i = 0; $i < 7; $i++) {
                    $dayStart = $cursorDay->copy()->startOfDay();
                    $dayEnd = $cursorDay->copy()->endOfDay();
                    $labelsTren[] = $cursorDay->translatedFormat('d M');
                    $seriesShipments[] = Pengiriman::whereBetween('created_at', [$dayStart, $dayEnd])
                        ->tap($filterLayanan)
                        ->count();
                    $seriesRevenue[] = (float) Pengiriman::where('status', 'terkirim')
                        ->whereBetween('created_at', [$dayStart, $dayEnd])
                        ->tap($filterLayanan)
                        ->sum('total_biaya');
                    $cursorDay->addDay();
                }
            } elseif ($period === '30_hari') {
                // 30 Hari terakhir: 6 blok tiap 5 hari
                $cursorBlock = $currentStart->copy();
                for ($i = 0; $i < 6; $i++) {
                    $bStart = $cursorBlock->copy()->startOfDay();
                    $bEnd = $cursorBlock->copy()->addDays(4)->endOfDay();
                    $labelsTren[] = $bStart->translatedFormat('d') . '-' . $bEnd->translatedFormat('d M');
                    $seriesShipments[] = Pengiriman::whereBetween('created_at', [$bStart, $bEnd])
                        ->tap($filterLayanan)
                        ->count();
                    $seriesRevenue[] = (float) Pengiriman::where('status', 'terkirim')
                        ->whereBetween('created_at', [$bStart, $bEnd])
                        ->tap($filterLayanan)
                        ->sum('total_biaya');
                    $cursorBlock->addDays(5);
                }
            } else {
                // Default / Bulan Ini / Bulan Lalu / Tahun Ini: 6 bulan tren
                $cursorMonth = now()->startOfMonth()->subMonths(5);
                $dbDriver = DB::connection()->getDriverName();
                $dateFormatRaw = $dbDriver === 'pgsql' ? "TO_CHAR(created_at, 'YYYY-MM')" : "DATE_FORMAT(created_at, '%Y-%m')";

                $monthlyShipments = Pengiriman::selectRaw("$dateFormatRaw as ym, COUNT(*) as total")
                    ->whereBetween('created_at', [$now->copy()->startOfMonth()->subMonths(5), $now->copy()->endOfMonth()])
                    ->tap($filterLayanan)
                    ->groupBy('ym')
                    ->pluck('total', 'ym')
                    ->toArray();

                $monthlyRevenue = Pengiriman::selectRaw("$dateFormatRaw as ym, COALESCE(SUM(total_biaya), 0) as total")
                    ->where('status', 'terkirim')
                    ->whereBetween('created_at', [$now->copy()->startOfMonth()->subMonths(5), $now->copy()->endOfMonth()])
                    ->tap($filterLayanan)
                    ->groupBy('ym')
                    ->pluck('total', 'ym')
                    ->toArray();

                for ($i = 0; $i < 6; $i++) {
                    $ym = $cursorMonth->format('Y-m');
                    $labelsTren[] = $cursorMonth->translatedFormat('M Y');
                    $seriesShipments[] = (int) ($monthlyShipments[$ym] ?? 0);
                    $seriesRevenue[] = (float) ($monthlyRevenue[$ym] ?? 0);
                    $cursorMonth->addMonth();
                }
            }

            return [
                'stats' => [
                    'totalPengiriman' => $totalPengiriman,
                    'totalPendapatan' => $totalPendapatan,
                    'pengirimanDelta' => $pengirimanDelta,
                    'pengirimanDeltaPercent' => $pengirimanDeltaPercent,
                    'pendapatanDeltaPercent' => $pendapatanDeltaPercent,
                    'successRate' => $successRatePercent,
                    'successRateDeltaPercent' => $successRateDeltaPercent,
                    'dalamProses' => $dalamProses,
                    'dalamProsesDeltaPercent' => $dalamProsesDeltaPercent,
                    'paketTerkirim' => $terkirim,
                    'terkirimDeltaPercent' => $terkirimDeltaPercent,
                    'paketBermasalah' => $paketBermasalah,
                    'bermasalahDeltaPercent' => $bermasalahDeltaPercent,
                ],
                'charts' => [
                    'area' => [
                        'labels' => $labelsTren,
                        'seriesRevenue' => $seriesRevenue,
                        'seriesShipments' => $seriesShipments,
                        'labels6' => $labelsTren,
                        'seriesRevenue6' => $seriesRevenue,
                        'seriesShipments6' => $seriesShipments,
                    ],
                    'status' => [
                        'terkirim' => $terkirim,
                        'dalam_perjalanan' => $dalamPerjalananCount,
                        'diproses' => $diprosesOnlyCount,
                        'bermasalah' => $paketBermasalah,
                    ],
                    'layanan' => [
                        'reguler' => (int) ($layananCounts['reguler'] ?? 0),
                        'express' => (int) ($layananCounts['express'] ?? 0),
                        'ekonomi' => (int) ($layananCounts['ekonomi'] ?? 0),
                        'kargo' => (int) ($layananCounts['kargo'] ?? 0),
                    ],
                    'topKotaList' => $topKotaList,
                ],
                'filters' => [
                    'period' => $period,
                    'period_label' => $periodLabel,
                    'start_date' => $currentStart->format('Y-m-d'),
                    'end_date' => $currentEnd->format('Y-m-d'),
                    'layanan' => $layanan,
                    'delta_label' => $deltaLabel,
                ],
            ];
        });

        // ── 7. Pengiriman Terbaru (5 baris sesuai filter aktif) ──────────────
        $data['latest'] = Pengiriman::query()
            ->select(['id', 'nomor_resi', 'pengirim_nama', 'penerima_kota_id', 'jenis_layanan', 'status', 'created_at', 'estimasi_tiba'])
            ->with(['penerimaKota:id,nama_kota'])
            ->whereBetween('created_at', [$currentStart, $currentEnd])
            ->when($layanan && $layanan !== 'semua', fn ($q) => $q->where('jenis_layanan', $layanan))
            ->orderByDesc('created_at')
            ->paginate(5)
            ->withQueryString()
            ->through(function ($p): array {
                /** @var Pengiriman $p */
                $isTerlambat = $p->estimasi_tiba->lt(today()) && ! in_array($p->status, ['terkirim', 'dibatalkan', 'gagal'], true);

                return [
                    'id' => $p->id,
                    'nomor_resi' => $p->nomor_resi,
                    'pengirim_nama' => $p->pengirim_nama,
                    'tujuan_kota' => $p->getRelationValue('penerimaKota')->nama_kota ?? null,
                    'jenis_layanan' => $p->jenis_layanan,
                    'status' => $p->status,
                    'created_at' => $p->created_at->toISOString(),
                    'is_terlambat' => $isTerlambat,
                ];
            });

        // Pastikan filter selalu dikirimkan ke frontend
        $data['filters'] = [
            'period' => $period,
            'period_label' => $periodLabel,
            'start_date' => $currentStart->format('Y-m-d'),
            'end_date' => $currentEnd->format('Y-m-d'),
            'layanan' => $layanan,
            'delta_label' => $deltaLabel,
        ];

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
