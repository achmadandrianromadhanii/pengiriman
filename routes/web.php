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
    Route::get('/', fn () => redirect()->route('dashboard'));

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

    // Kota (CRUD)
    Route::get('/kota', [KotaController::class, 'index'])->name('kota.index');
    Route::post('/kota', [KotaController::class, 'store'])->name('kota.store');
    Route::put('/kota/{kota}', [KotaController::class, 'update'])->name('kota.update');
    Route::delete('/kota/{kota}', [KotaController::class, 'destroy'])->name('kota.destroy');
    Route::post('/kota/import', [KotaController::class, 'import'])->name('kota.import');

    // Logout
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
