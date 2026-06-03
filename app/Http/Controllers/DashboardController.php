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
            $dateFormatRaw = $dbDriver === 'pgsql'
                ? "TO_CHAR(created_at, 'YYYY-MM')"
                : "DATE_FORMAT(created_at, '%Y-%m')";

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

            return [
                'stats' => [
                    'totalPengiriman' => $totalPengiriman,
                    'totalPendapatan' => $totalPendapatan,
                    'pengirimanDelta' => $pengirimanDelta,
                    'pendapatanDeltaPercent' => $pendapatanDeltaPercent,
                    'successRate' => $successRatePercent,
                    'paketAktif' => $diproses,
                    'paketBermasalah' => $terlambat + $gagalBatal,
                ],
                'charts' => [
                    'area' => [
                        'labels' => $labels,
                        'seriesRevenue' => $seriesR,
                        'seriesShipments' => $seriesS,
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
