<?php

namespace App\Http\Controllers;

// =========================================================================
// CONTROLLER: ProfileController — Manajemen profil admin
// =========================================================================
// Fungsi: Menampilkan form profil, menyimpan perubahan, dan menghapus akun.
// Cara Kerja:
//   - edit(): Render halaman Profile/Edit.vue dengan data user
//   - update(): Simpan nama/email/password + [UPDATE] set password_changed_at
//   - destroy(): Logout + hapus akun permanen
// [UPDATE: SECURITY MODULE]
//   - Saat password berubah, kolom password_changed_at di-update ke now().
// =========================================================================

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     * [UPDATE: SECURITY] Jika password berubah, set password_changed_at.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // ── [UPDATE: SECURITY] Deteksi apakah password diubah ────────
        if (!empty($validated['password'])) {
            $user->password_changed_at = now();
            
            // Kirim Notifikasi via DB & Pusher
            $user->notify(new \App\Notifications\SecurityAlert([
                'title' => 'Password Berhasil Diubah',
                'message' => 'Password akun Anda telah berhasil diperbarui.',
                'category' => 'success',
            ]));
        }

        $user->fill($validated);

        if ($request->hasFile('foto')) {
            if ($user->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->foto)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($user->foto);
            }
            $user->foto = $request->file('foto')->store('avatars', 'public');
        }

        $user->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
    /**
     * =========================================================================
     * deleteAccountPage() — Render halaman Hapus Akun (Mobile Web)
     * =========================================================================
     * Fungsi: Menampilkan halaman Profile/DeleteAccount.vue beserta data audit.
     * Cara Kerja:
     *   - 5 riwayat terbaru ditampilkan di halaman utama
     *   - Sisanya (skip 5, take 100) masuk ke archive popup
     *   - Data diurutkan newest → oldest (orderBy desc)
     * =========================================================================
     */
    public function deleteAccountPage()
    {
        $recentDeletions = \App\Models\AccountDeletion::orderBy('created_at', 'desc')->take(5)->get();
        $archivedDeletions = \App\Models\AccountDeletion::orderBy('created_at', 'desc')->skip(5)->take(100)->get();

        return Inertia::render('Profile/DeleteAccount', [
            'recentDeletions' => $recentDeletions,
            'archivedDeletions' => $archivedDeletions,
        ]);
    }

    /**
     * =========================================================================
     * deleteAccountSubmit() — Proses penghapusan akun secara permanen
     * =========================================================================
     * Fungsi: Menghapus akun admin secara atomic dan aman.
     * Cara Kerja (FLOW):
     *   1. Validasi password (current_password rule Laravel)
     *   2. Validasi reason (wajib, string, maks 500 karakter)
     *   3. Buat audit record di tabel account_deletions
     *   4. Logout user dari session saat ini
     *   5. Hapus akun secara permanen ($user->delete())
     *   6. Invalidasi session + regenerate CSRF token
     *   7. Redirect ke halaman login
     *
     * Keamanan:
     *   - Password TIDAK disimpan di audit (hanya diverifikasi)
     *   - Session dicabut total → akun tidak bisa dipakai lagi
     *   - Remember token otomatis invalid karena user dihapus
     * =========================================================================
     */
    public function deleteAccountSubmit(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();

        // STEP 1: Simpan audit record sebelum akun dihapus
        \App\Models\AccountDeletion::create([
            'email' => $user->email,
            'reason' => $request->reason,
            'actor_name' => $user->name,
        ]);

        // STEP 2: Logout dari authentication guard
        Auth::logout();

        // STEP 3: Hapus akun secara permanen dari database
        $user->delete();

        // STEP 4: Cabut session + regenerate CSRF token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // STEP 5: Redirect ke halaman login
        return Redirect::to('/');
    }
}
