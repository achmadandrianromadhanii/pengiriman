<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengirimanRequest;
use App\Models\Kota;
use App\Models\Pengiriman;
use App\Models\Tarif;
use App\Services\ResiService;
use App\Services\TarifService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PengirimanController extends Controller
{
    private const FINAL_STATUSES = ['terkirim', 'dibatalkan', 'gagal'];

    public function index(Request $request): Response
    {
        $sort = $request->string('sort', 'terbaru')->toString();
        $status = $request->string('status', 'all')->toString();

        $dari = $request->string('dari', (string) $request->input('start_date', ''))->toString();
        $sampai = $request->string('sampai', (string) $request->input('end_date', ''))->toString();

        $perPage = $request->integer('perPage', $request->integer('per_page', 10));
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        $query = Pengiriman::query()
            ->select([
                'id',
                'nomor_resi',
                'pengirim_nama',
                'pengirim_hp',
                'penerima_nama',
                'penerima_hp',
                'pengirim_kota_id',
                'penerima_kota_id',
                'jenis_layanan',
                'status',
                'estimasi_tiba',
                'total_biaya',
                'created_at',
            ])
            ->with([
                'penerimaKota:id,nama_kota',
                'pengirimKota:id,nama_kota',
            ]);

        if ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        $dariValid = $this->isDateYmd($dari);
        $sampaiValid = $this->isDateYmd($sampai);

        if ($dariValid && $sampaiValid) {
            $query->whereBetween('created_at', [
                $dari.' 00:00:00',
                $sampai.' 23:59:59',
            ]);
        } elseif ($dariValid) {
            $query->where('created_at', '>=', $dari.' 00:00:00');
        } elseif ($sampaiValid) {
            $query->where('created_at', '<=', $sampai.' 23:59:59');
        }

        $sortNormalized = match ($sort) {
            'az_resi' => 'resi_az',
            default => $sort,
        };

        match ($sortNormalized) {
            'terlama' => $query->orderBy('created_at', 'asc'),
            'terlambat' => $query
                ->orderByDesc(DB::raw("estimasi_tiba < '".now()->toDateString()."' AND status NOT IN ('terkirim','dibatalkan','gagal')"))
                ->orderBy('estimasi_tiba', 'asc')
                ->orderByDesc('created_at'),
            'resi_az' => $query->orderBy('nomor_resi', 'asc'),
            'biaya_terbesar' => $query->orderByDesc('total_biaya')->orderByDesc('created_at'),
            default => $query->orderByDesc('created_at'),
        };

        $pengiriman = $query->paginate($perPage)->withQueryString();

        $today = now()->toDateString();

        $pengiriman->getCollection()->transform(function (Pengiriman $p) use ($today) {
            $estimasi = $p->estimasi_tiba;

            // Penjelasan: Mengambil estimasi_tiba secara langsung. Karena tipe data sudah di-cast
            // menjadi Carbon di Model, kita bisa langsung menggunakan method toDateString() tanpa harus dicek null atau instanceof
            $estimasiDate = $estimasi->toDateString();

            $isTerlambat = $estimasiDate !== ''
                && $estimasiDate < $today
                && ! in_array((string) $p->status, self::FINAL_STATUSES, true);

            $p->setAttribute('is_terlambat', $isTerlambat);
            // Penjelasan: Menggunakan getRelationValue('penerimaKota') agar secara strict PHPStan mengerti
            // ini adalah pemanggilan relasi Eloquent yang sah dan untuk mencegah pesan error undefined property di sisi PHPStan.
            $p->setAttribute('tujuan_kota', $p->getRelationValue('penerimaKota')->nama_kota ?? '-');

            return $p;
        });

        return Inertia::render('Pengiriman/Index', [
            'pengiriman' => $pengiriman,
            'filters' => [
                'sort' => $sort,
                'status' => $status,
                'start_date' => $dari,
                'end_date' => $sampai,
                'per_page' => $perPage,

                'dari' => $dari,
                'sampai' => $sampai,
                'perPage' => $perPage,
            ],
        ]);
    }

    public function create(): Response
    {
        $kota = Cache::remember('kota_aktif_list_create', 3600, function () {
            return Kota::aktif()
                ->orderBy('nama_kota')
                ->get(['id', 'nama_kota', 'provinsi', 'kode_pos', 'latitude', 'longitude']);
        });

        return Inertia::render('Pengiriman/Create', [
            'kota' => $kota,
        ]);
    }

    public function store(PengirimanRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $barangList = $validated['barang'];

        $adminId = (int) Auth::guard('web')->id();

        $pengiriman = DB::transaction(function () use ($validated, $barangList, $adminId): Pengiriman {
            // ── berat asli ────────────────────────────────────────────────
            $totalBeratAsli = (float) collect($barangList)->sum(
                fn (array $b): float => (float) ($b['berat_kg'] ?? 0)
            );
            $totalBeratAsli = max(0.01, round($totalBeratAsli, 2));

            // ── volume & volumetrik ───────────────────────────────────────
            $totalVolumeCm3 = $this->calcTotalVolumeCm3($barangList);
            $beratVolumetrik = $this->calcVolumetricKg($totalVolumeCm3);

            // ── chargeable weight = max(asli, volumetrik) ─────────────────
            $beratDitagihkan = $this->calcChargeableKg($totalBeratAsli, $beratVolumetrik);

            $jumlahBarang = count($barangList);

            // ── tarif sesuai rute + layanan + range berat ─────────────────
            // ✅ SECURITY + KONSISTEN: hitung ulang ongkir seperti TarifService
            $tarifService = app(TarifService::class);
            $hasilTarifList = $tarifService->hitung(
                (int) $validated['pengirim_kota_id'],
                (int) $validated['penerima_kota_id'],
                (float) $totalBeratAsli,
                (float) $totalVolumeCm3
            );

            // Cari tarif yang sesuai dengan jenis_layanan yang dipilih user
            $tarifTerpilih = collect($hasilTarifList)->firstWhere('jenis_layanan', $validated['jenis_layanan']);

            if (! $tarifTerpilih) {
                throw ValidationException::withMessages([
                    'jenis_layanan' => 'Tarif untuk rute dan layanan ini tidak tersedia.',
                ]);
            }

            $estimasiHari = (int) ($tarifTerpilih['estimasi_hari'] ?? 3);
            $estimasiHari = $estimasiHari > 0 ? $estimasiHari : 3;

            // biaya_pengiriman dari client DIABAIKAN
            $biayaPengiriman = (float) $tarifTerpilih['total'];

            $biayaTambahan = max(0.0, (float) ($validated['biaya_tambahan'] ?? 0));
            $biayaAsuransi = max(0.0, (float) ($validated['biaya_asuransi'] ?? 0));

            $totalBiaya = $biayaPengiriman + $biayaTambahan + $biayaAsuransi;

            $lokasiAsal = Kota::query()
                ->whereKey($validated['pengirim_kota_id'])
                ->value('nama_kota') ?? '-';

            $pengiriman = Pengiriman::create([
                'nomor_resi' => app(ResiService::class)->generate(),

                'pengirim_nama' => $validated['pengirim_nama'],
                'pengirim_hp' => $validated['pengirim_hp'],
                'pengirim_alamat' => $validated['pengirim_alamat'],
                'pengirim_kota_id' => $validated['pengirim_kota_id'],

                'penerima_nama' => $validated['penerima_nama'],
                'penerima_hp' => $validated['penerima_hp'],
                'penerima_alamat' => $validated['penerima_alamat'],
                'penerima_kota_id' => $validated['penerima_kota_id'],

                // simpan berat asli (data fisik)
                'total_berat_kg' => $totalBeratAsli,
                'jumlah_barang' => $jumlahBarang,

                'jenis_layanan' => $validated['jenis_layanan'],

                'biaya_pengiriman' => $biayaPengiriman,
                'biaya_tambahan' => $biayaTambahan,
                'biaya_asuransi' => $biayaAsuransi,
                'total_biaya' => $totalBiaya,

                'metode_pembayaran' => $validated['metode_pembayaran'],
                'status_pembayaran' => 'lunas',

                'status' => 'pending',
                'estimasi_tiba' => now()->addDays($estimasiHari)->toDateString(),
                'catatan' => $validated['catatan'] ?? null,

                'admin_id' => $adminId,
            ]);

            $pengiriman->barang()->createMany(
                collect($barangList)->map(fn (array $barang): array => [
                    'nama_barang' => (string) $barang['nama_barang'],
                    'berat_kg' => (float) ($barang['berat_kg'] ?? 0),
                    'panjang_cm' => (float) ($barang['panjang_cm'] ?? 0),
                    'lebar_cm' => (float) ($barang['lebar_cm'] ?? 0),
                    'tinggi_cm' => (float) ($barang['tinggi_cm'] ?? 0),
                    'keterangan' => $barang['keterangan'] ?? null,
                ])->all()
            );

            $pengiriman->trackingHistories()->create([
                'status_lama' => null,
                'status_baru' => 'pending',
                'lokasi' => $lokasiAsal,
                'keterangan' => 'Pengiriman berhasil dibuat dan menunggu diproses.',
                'admin_id' => $adminId,
            ]);

            return $pengiriman;
        });

        return redirect()
            ->route('pengiriman.show', $pengiriman)
            ->with('success', "Pengiriman berhasil dibuat! Nomor Resi: {$pengiriman->nomor_resi}");
    }

    public function show(Pengiriman $pengiriman): Response
    {
        $pengiriman->load([
            'barang',
            'admin:id,nama,email',
            'pengirimKota:id,nama_kota,provinsi,kode_pos,latitude,longitude',
            'penerimaKota:id,nama_kota,provinsi,kode_pos,latitude,longitude',
            'trackingHistories.admin:id,nama',
        ]);

        $today = now()->toDateString();

        $estimasi = $pengiriman->estimasi_tiba;
        // Penjelasan: Sama seperti di atas, mengakses toDateString() secara langsung dari Carbon instance yang telah di-cast,
        // menghapus tipe instanceof yang selalu mereturn true menurut PHPStan.
        $estimasiDate = $estimasi->toDateString();

        $isTerlambat = $estimasiDate !== ''
            && $estimasiDate < $today
            && ! in_array((string) $pengiriman->status, self::FINAL_STATUSES, true);

        $canCancel = ! in_array((string) $pengiriman->status, self::FINAL_STATUSES, true);

        $pengiriman->setAttribute('is_terlambat', $isTerlambat);
        $pengiriman->setAttribute('can_cancel', $canCancel);

        return Inertia::render('Pengiriman/Show', [
            'pengiriman' => $pengiriman,
        ]);
    }

    public function batal(Request $request, Pengiriman $pengiriman): RedirectResponse
    {
        if (in_array((string) $pengiriman->status, self::FINAL_STATUSES, true)) {
            return back()->with('error', 'Pengiriman tidak bisa dibatalkan karena status sudah final.');
        }

        $validated = $request->validate(
            [
                'alasan_batal' => 'required|string|min:5|max:500',
            ],
            [
                'alasan_batal.required' => 'Alasan pembatalan wajib diisi.',
                'alasan_batal.min' => 'Alasan pembatalan minimal 5 karakter.',
                'alasan_batal.max' => 'Alasan pembatalan maksimal 500 karakter.',
            ]
        );

        $adminId = (int) Auth::guard('web')->id();

        DB::transaction(function () use ($pengiriman, $validated, $adminId): void {
            $statusLama = (string) $pengiriman->status;

            $pengiriman->forceFill([
                'status' => 'dibatalkan',
                'alasan_batal' => $validated['alasan_batal'],
            ])->save();

            $lokasiAsal = Kota::query()
                ->whereKey($pengiriman->pengirim_kota_id)
                ->value('nama_kota') ?? '-';

            $pengiriman->trackingHistories()->create([
                'status_lama' => $statusLama,
                'status_baru' => 'dibatalkan',
                'lokasi' => $lokasiAsal,
                'keterangan' => 'Pengiriman dibatalkan. Alasan: '.$validated['alasan_batal'],
                'admin_id' => $adminId,
            ]);
        });

        return back()->with('success', 'Pengiriman berhasil dibatalkan.');
    }

    private function isDateYmd(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }

    /**
     * Total volume cm³ = Σ(P×L×T) semua barang.
     */
    private function calcTotalVolumeCm3(array $barangList): float
    {
        $sum = 0.0;

        foreach ($barangList as $b) {
            $p = max(0.0, (float) ($b['panjang_cm'] ?? 0));
            $l = max(0.0, (float) ($b['lebar_cm'] ?? 0));
            $t = max(0.0, (float) ($b['tinggi_cm'] ?? 0));

            $sum += ($p * $l * $t);
        }

        return round($sum, 2);
    }

    /**
     * Berat volumetrik (kg) = volume(cm³) / 6000.
     */
    private function calcVolumetricKg(float $volumeCm3): float
    {
        if ($volumeCm3 <= 0) {
            return 0.0;
        }

        return round($volumeCm3 / 6000, 2);
    }

    /**
     * Berat ditagihkan = max(berat asli, berat volumetrik).
     */
    private function calcChargeableKg(float $actualKg, float $volumetricKg): float
    {
        $a = max(0.0, $actualKg);
        $v = max(0.0, $volumetricKg);

        return max(0.01, round(max($a, $v), 2));
    }
}
