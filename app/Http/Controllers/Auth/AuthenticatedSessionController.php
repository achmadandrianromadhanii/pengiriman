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
    // ── Render halaman login ─────────────────────────────────────────────
    public function create(): InertiaResponse
    {
        return Inertia::render('Auth/Login');
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

    // ── Logout admin ────────────────────────────────────────────────────
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    // =====================================================================
    // [UPDATE: SECURITY] Logout semua perangkat lain
    // =====================================================================
    // Fungsi: Menghapus semua sesi aktif kecuali sesi saat ini.
    // Cara Kerja: Hapus semua record sessions di database yang memiliki
    //             user_id = admin saat ini KECUALI session_id saat ini.
    // =====================================================================
    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $sessionId = $request->session()->getId();
        $userId = Auth::id();

        // Hapus semua sesi lain dari tabel sessions
        DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $sessionId)
            ->delete();

        return back();
    }
}
