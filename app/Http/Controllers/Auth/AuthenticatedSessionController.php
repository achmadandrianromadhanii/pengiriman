<?php

namespace App\Http\Controllers\Auth;

// =========================================================================
// CONTROLLER: AuthenticatedSessionController — Login & Logout Admin
// =========================================================================
// Fungsi: Menangani proses autentikasi admin (login, logout).
// Cara Kerja:
//   - create(): Render halaman login (Auth/Login.vue)
//   - store(): Validasi credential → login → catat riwayat [UPDATE: SECURITY]
//   - destroy(): Logout → invalidate session → redirect ke login
// [UPDATE: SECURITY MODULE]
//   - Setiap login berhasil, buat record di login_histories dengan metadata
//     perangkat (browser, OS, device type, IP address).
//   - Menambahkan method logoutOtherDevices() untuk logout semua perangkat.
// =========================================================================

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\Admin;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthenticatedSessionController extends Controller
{
    // =====================================================================
    // Render halaman login [UPDATE: V3]
    // =====================================================================
    // Fungsi: Menampilkan halaman login dengan informasi biometrik.
    // Cara Kerja: Cek apakah ada admin dengan biometrik aktif, lalu
    //             kirim flag biometric_active ke frontend.
    // =====================================================================
    public function create(): InertiaResponse
    {
        // [UPDATE: V3] Cek apakah ada kredensial WebAuthn terdaftar
        // Ini menentukan apakah bottom sheet biometrik muncul di halaman login.
        $hasBiometric = \App\Models\WebauthnCredential::exists();

        return Inertia::render('Auth/Login', [
            'biometric_active' => $hasBiometric,
        ]);
    }

    // =====================================================================
    // LOGIN: Validasi credential + catat riwayat login
    // =====================================================================
    // Cara Kerja:
    //   1. Validasi email & password dari request
    //   2. Cari admin berdasarkan email
    //   3. Cek password hash & status aktif
    //   4. Login via Auth::login()
    //   5. [UPDATE] Catat login ke tabel login_histories
    //   6. Update last_login timestamp
    //   7. Return response sukses ke frontend
    // =====================================================================
    public function store(LoginRequest $request): InertiaResponse|RedirectResponse
    {
        $validated = $request->validated();

        $admin = Admin::query()
            ->where('email', $validated['email'])
            ->first();

        if (! $admin || ! Hash::check($validated['password'], $admin->password)) {
            return back()
                ->withErrors(['email' => 'Email atau password salah'])
                ->onlyInput('email');
        }

        if ($admin->status !== 'aktif') {
            return back()
                ->withErrors(['email' => 'Akun tidak aktif'])
                ->onlyInput('email');
        }

        Auth::login($admin, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        // ── [UPDATE: SECURITY] Catat riwayat login ke database ───────
        $ua = $request->userAgent() ?? '';
        $parsed = LoginHistory::parseUserAgent($ua);

        LoginHistory::create([
            'admin_id'    => $admin->id,
            'ip_address'  => $request->ip(),
            'user_agent'  => $ua,
            'browser'     => $parsed['browser'],
            'os'          => $parsed['os'],
            'device_type' => $parsed['device_type'],
            'logged_in_at'=> now(),
        ]);

        // ── Update last_login timestamp ──────────────────────────────
        $admin->forceFill([
            'last_login' => now(),
        ])->save();

        return Inertia::render('Auth/Login', [
            'loginSuccess' => true,
        ]);
    }

    // =====================================================================
    // [UPDATE: V3] LOGIN BIOMETRIK: Verifikasi credential_id
    // =====================================================================
    // Fungsi: Menerima credential.id dari frontend (WebAuthn), memverifikasinya,
    //         dan melakukan login jika cocok dengan database.
    // Cara Kerja:
    //   1. Validasi credential_id
    //   2. Cari di tabel webauthn_credentials
    //   3. Dapatkan admin_id, verifikasi status aktif
    //   4. Auth::login() + catat riwayat login
    // =====================================================================
    public function storeBiometric(Request $request): InertiaResponse|RedirectResponse
    {
        $validated = $request->validate([
            'credential_id' => 'required|string',
        ]);

        // Cari kredensial di database
        $credential = \App\Models\WebauthnCredential::where('credential_id', $validated['credential_id'])->first();

        if (! $credential) {
            return back()->withErrors(['biometric' => 'Kredensial biometrik tidak ditemukan atau belum terdaftar.']);
        }

        // Ambil data admin terkait
        $admin = Admin::find($credential->admin_id);

        if (! $admin || $admin->status !== 'aktif') {
            return back()->withErrors(['biometric' => 'Akun tidak aktif atau tidak ditemukan.']);
        }

        // Login (dengan opsi remember = true)
        Auth::login($admin, true);
        $request->session()->regenerate();

        // ── [UPDATE: SECURITY] Catat riwayat login ke database ───────
        $ua = $request->userAgent() ?? '';
        $parsed = LoginHistory::parseUserAgent($ua);

        LoginHistory::create([
            'admin_id'    => $admin->id,
            'ip_address'  => $request->ip(),
            'user_agent'  => $ua,
            'browser'     => $parsed['browser'],
            'os'          => $parsed['os'],
            'device_type' => $parsed['device_type'],
            'logged_in_at'=> now(),
        ]);

        // ── Update last_login timestamp ──────────────────────────────
        $admin->forceFill([
            'last_login' => now(),
        ])->save();

        return Inertia::render('Auth/Login', [
            'loginSuccess' => true,
        ]);
    }

    // ── Logout admin ────────────────────────────────────────────────────
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // =====================================================================
    // [UPDATE: SECURITY V4] Logout semua perangkat (termasuk saat ini)
    // =====================================================================
    // Fungsi: Menghapus semua sesi aktif di server.
    // Cara Kerja:
    //   - Jika request memiliki logout_current = true:
    //     → Hapus SEMUA sesi dari tabel sessions (termasuk sesi ini).
    //     → Logout user saat ini (Auth::logout).
    //     → Invalidate session + regenerate CSRF token.
    //     → Redirect ke halaman login.
    //   - Jika logout_current = false (default):
    //     → Hapus semua sesi KECUALI sesi saat ini.
    //     → Kembali ke halaman sebelumnya.
    // =====================================================================
    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $userId = Auth::id();
        $logoutCurrent = $request->boolean('logout_current', false);

        if ($logoutCurrent) {
            // [V4] Hapus SELURUH sesi dari database (termasuk sesi saat ini)
            DB::table('sessions')
                ->where('user_id', $userId)
                ->delete();

            // Logout user saat ini
            Auth::logout();

            // Invalidate session & regenerate CSRF token
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Redirect ke halaman login
            return redirect()->route('login');
        }

        // Default: hapus sesi lain, pertahankan sesi saat ini
        $sessionId = $request->session()->getId();
        DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $sessionId)
            ->delete();

        return back();
    }
}
