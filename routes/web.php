<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KotaController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PengirimanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResiController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TarifController;
use App\Http\Controllers\TarifManagementController;
use App\Http\Controllers\TrackingController;
use Illuminate\Support\Facades\Route;

// ─── AUTH ────────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

// ─── PUBLIK — Tracking tanpa login ──────────────────────────────────
Route::get('/tracking', [TrackingController::class, 'search'])
    ->name('tracking.search')
    ->middleware('throttle:tracking');

Route::get('/tracking/{nomor_resi}', [TrackingController::class, 'public'])
    ->name('tracking.public')
    ->middleware('throttle:tracking')
    ->where('nomor_resi', '^SS-\d{6}-\d{4}$');

// ─── ADMIN — semua butuh auth + admin aktif ───────────────────────────
Route::middleware(['auth', 'admin.active'])->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'));

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pengiriman
    Route::get('/pengiriman', [PengirimanController::class, 'index'])->name('pengiriman.index');
    Route::get('/pengiriman/create', [PengirimanController::class, 'create'])->name('pengiriman.create');
    Route::post('/pengiriman', [PengirimanController::class, 'store'])->name('pengiriman.store');
    Route::get('/pengiriman/{pengiriman}', [PengirimanController::class, 'show'])->name('pengiriman.show');
    Route::post('/pengiriman/{pengiriman}/tracking', [TrackingController::class, 'update'])->name('tracking.update');
    Route::post('/pengiriman/{pengiriman}/batal', [PengirimanController::class, 'batal'])->name('pengiriman.batal');

    // Resi
    Route::get('/resi/{pengiriman}/print', [ResiController::class, 'print'])->name('resi.print');
    Route::get('/resi/{pengiriman}/pdf', [ResiController::class, 'pdf'])->name('resi.pdf');

    // Cek Tarif (publik form + API cek)
    Route::get('/tarif', [TarifController::class, 'index'])->name('tarif.index');
    Route::post('/tarif/cek', [TarifController::class, 'cek'])->name('tarif.cek')->middleware('throttle:30,1');

    // Manajemen Tarif (CRUD di settings)
    Route::resource('tarif-data', TarifManagementController::class)->except(['show', 'create', 'edit']);

    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');

    // =========================================================================
    // PENGATURAN (SETTINGS)
    // =========================================================================
    // Menampilkan halaman pengaturan umum aplikasi (seperti Data Kota, Tarif)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');

    // =========================================================================
    // PROFIL PENGGUNA (PROFILE)
    // =========================================================================
    // Rute-rute ini menangani operasi pada profil pengguna (Edit, Update, Hapus)
    // - profile.edit: Menampilkan form form edit profil (Profile/Edit.vue)
    // - profile.update: Memproses penyimpanan data profil baru (Nama, Email, Password)
    // - profile.destroy: Memproses penghapusan akun secara permanen
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // =========================================================================
    // MENU MOBILE / SYSTEM — DATA ASLI DARI BACKEND
    // =========================================================================

    // ── Tentang Aplikasi ─────────────────────────────────────────────────
    // Fungsi: Mengirim metadata sistem ASLI ke halaman About.
    // Cara Kerja: Membaca versi Laravel, PHP, PostgreSQL, Vue, Vite
    //             secara dinamis dari runtime dan package.json.
    Route::get('/system/about', function () {
        // [FIX] Gunakan DB facade langsung sesuai dokumentasi Laravel

        // ── Versi PostgreSQL (query langsung ke server) ──────────────
        $pgVersion = 'N/A';
        try {
            $row = \Illuminate\Support\Facades\DB::selectOne("SELECT current_setting('server_version') AS v");
            $pgVersion = $row->v ?? 'N/A';
        } catch (\Throwable $e) {
            $pgVersion = 'N/A';
        }

        // ── Versi Vue & Vite dari package.json ───────────────────────
        $vueVersion = '3.x';
        $viteVersion = '8.x';
        $pkgPath = base_path('package.json');
        if (file_exists($pkgPath)) {
            $pkg = json_decode(file_get_contents($pkgPath), true);
            $deps = array_merge($pkg['dependencies'] ?? [], $pkg['devDependencies'] ?? []);
            $vueVersion = ltrim($deps['vue'] ?? '3.x', '^~');
            $viteVersion = ltrim($deps['vite'] ?? '8.x', '^~');
        }

        // ── Status koneksi database ──────────────────────────────
        $dbStatus = 'disconnected';
        try {
            \Illuminate\Support\Facades\DB::selectOne('SELECT 1');
            $dbStatus = 'connected';
        } catch (\Throwable $e) {
            $dbStatus = 'error';
        }

        return Inertia\Inertia::render('System/About', [
            'system' => [
                'app_version'    => '5.0.0',
                'build'          => date('Y.m') . '.Rev1',
                'laravel'        => app()->version(),
                'php'            => phpversion(),
                'vue'            => $vueVersion,
                'vite'           => $viteVersion,
                'pgsql'          => $pgVersion,
                'environment'    => app()->environment(),
                'timezone'       => config('app.timezone'),
                'storage_driver' => config('filesystems.default'),
                'db_driver'      => config('database.default'),
                'db_status'      => $dbStatus,
                'server_status'  => 'online',
            ],
        ]);
    })->name('system.about');

    // ── Cache (frontend-only, tidak butuh data backend) ──────────────
    // Fungsi: Halaman ini mengambil data cache dari Browser API langsung.
    // Tidak perlu data dari server. Hanya render halaman kosong.
    Route::get('/system/cache', function () {
        return Inertia\Inertia::render('System/Cache');
    })->name('system.cache');

    // ── Penyimpanan Database ─────────────────────────────────────────
    // Fungsi: Mengirim statistik database ASLI dari PostgreSQL/Neon.
    // Cara Kerja: Query pg_database_size() untuk ukuran, COUNT untuk jumlah record.
    Route::get('/system/storage', function () {
        // [FIX] Gunakan DB facade langsung sesuai dokumentasi Laravel

        // ── Ukuran database asli (PostgreSQL) ────────────────────
        $dbSizeBytes = 0;
        try {
            $row = \Illuminate\Support\Facades\DB::selectOne("SELECT pg_database_size(current_database()) AS size");
            $dbSizeBytes = (int) ($row->size ?? 0);
        } catch (\Throwable $e) {
            $dbSizeBytes = 0;
        }

        // ── Format ukuran menjadi string yang mudah dibaca ───────────
        $formatBytes = function (int $bytes): string {
            if ($bytes <= 0) return '0 B';
            $units = ['B', 'KB', 'MB', 'GB'];
            $i = 0;
            $size = (float) $bytes;
            while ($size >= 1024 && $i < count($units) - 1) {
                $size /= 1024;
                $i++;
            }
            return round($size, 1) . ' ' . $units[$i];
        };

        // ── Hitung jumlah record dari semua tabel utama ──────────────
        $pengiriman = \App\Models\Pengiriman::count();
        $pelanggan  = \App\Models\Pengiriman::distinct('pengirim_nama')->count('pengirim_nama');
        $riwayat    = \App\Models\TrackingHistory::count();
        $admin      = \App\Models\Admin::count();
        $kota       = \App\Models\Kota::count();
        $tarif      = \App\Models\Tarif::count();

        // ── Lampiran: hitung dari tabel pengiriman_barang ────────────
        $lampiran = \App\Models\PengirimanBarang::count();

        // ── Total seluruh record ─────────────────────────────────────
        $totalRecords = $pengiriman + $pelanggan + $riwayat + $admin + $kota + $tarif + $lampiran;

        return Inertia\Inertia::render('System/Storage', [
            'database'      => config('database.default') === 'pgsql' ? 'Neon PostgreSQL' : 'MySQL Database',
            'db_size_bytes'  => $dbSizeBytes,
            'db_size'        => $formatBytes($dbSizeBytes),
            'pengiriman'    => $pengiriman,
            'pelanggan'     => $pelanggan,
            'riwayat'       => $riwayat,
            'lampiran'      => $lampiran,
            'admin'         => $admin,
            'kota'          => $kota,
            'tarif'         => $tarif,
            'total_records' => $totalRecords,
        ]);
    })->name('system.storage');

    // =====================================================================
    // KEAMANAN AKUN — DATA ASLI DARI DATABASE [UPDATE: SECURITY MODULE]
    // =====================================================================
    // Fungsi: Mengirim data keamanan ASLI ke halaman Security.
    // Cara Kerja:
    //   1. Ambil 10 riwayat login terakhir dari tabel login_histories
    //   2. Ambil semua sesi aktif dari tabel sessions
    //   3. Parse user_agent untuk setiap sesi aktif
    //   4. Kirim password_changed_at dari admin
    // =====================================================================
    Route::get('/profile/security', function () {
        $admin = \Illuminate\Support\Facades\Auth::user();

        // ── Riwayat Login Terakhir (10 terbaru) ─────────────────────
        $loginHistories = \App\Models\LoginHistory::where('admin_id', $admin->id)
            ->orderByDesc('logged_in_at')
            ->take(10)
            ->get()
            ->map(fn ($h) => [
                'id'          => $h->id,
                'ip_address'  => $h->ip_address,
                'browser'     => $h->browser,
                'os'          => $h->os,
                'device_type' => $h->device_type,
                'logged_in_at'=> $h->logged_in_at?->toIso8601String(),
            ]);

        // ── Sesi Aktif (dari tabel sessions) ────────────────────────
        // [FIX] Gunakan DB facade langsung, bukan getFacadeRoot()
        $currentSessionId = request()->session()->getId();
        $activeSessions = \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $admin->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($s) use ($currentSessionId) {
                $ua = $s->user_agent ?? '';
                $parsed = \App\Models\LoginHistory::parseUserAgent($ua);
                return [
                    'id'            => $s->id,
                    'ip_address'    => $s->ip_address,
                    'browser'       => $parsed['browser'],
                    'os'            => $parsed['os'],
                    'device_type'   => $parsed['device_type'],
                    'last_activity' => date('c', $s->last_activity),
                    'is_current'    => $s->id === $currentSessionId,
                ];
            });

        return Inertia\Inertia::render('Profile/Security', [
            'password_changed_at' => $admin->password_changed_at?->toIso8601String(),
            'last_login'          => $admin->last_login?->toIso8601String(),
            'login_histories'     => $loginHistories,
            'active_sessions'     => $activeSessions,
        ]);
    })->name('security.index');

    Route::get('/profile/password', function () {
        return Inertia\Inertia::render('Profile/Password');
    })->name('password.edit');

    // ── [UPDATE: SECURITY] Logout semua perangkat lain ───────────────
    Route::post('/profile/security/logout-others', [AuthenticatedSessionController::class, 'logoutOtherDevices'])
        ->name('security.logout-others');

    // Kota (CRUD)
    Route::get('/kota', [KotaController::class, 'index'])->name('kota.index');
    Route::post('/kota', [KotaController::class, 'store'])->name('kota.store');
    Route::put('/kota/{kota}', [KotaController::class, 'update'])->name('kota.update');
    Route::delete('/kota/{kota}', [KotaController::class, 'destroy'])->name('kota.destroy');
    Route::post('/kota/import', [KotaController::class, 'import'])->name('kota.import');

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
