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
        // Cara Kerja: Jika field 'password' ada di validated data,
        // berarti user mengirim password baru → set timestamp.
        if (!empty($validated['password'])) {
            $user->password_changed_at = now();
        }

        $user->fill($validated);
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
}
