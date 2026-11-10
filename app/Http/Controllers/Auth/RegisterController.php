<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\LoginHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    /**
     * Daftarkan akun admin baru dengan validasi password 5 kriteria popover.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'string', 'email:rfc,dns', 'max:150', 'unique:admins,email'],
            'password' => [
                'required',
                'string',
                'min:12',
                'regex:/[A-Z]/',        // Minimal 1 huruf besar
                'regex:/[a-z]/',        // Minimal 1 huruf kecil
                'regex:/[0-9]/',        // Minimal 1 angka
                'regex:/[^a-zA-Z0-9]/', // Minimal 1 karakter spesial
                'confirmed',
            ],
        ], [
            'name.required'      => 'Nama lengkap wajib diisi.',
            'email.required'     => 'Alamat email wajib diisi.',
            'email.email'        => 'Format alamat email tidak valid.',
            'email.unique'       => 'Alamat email ini sudah terdaftar. Silakan login atau gunakan email lain.',
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password harus memiliki panjang minimal 12 karakter.',
            'password.regex'     => 'Password harus memenuhi seluruh syarat popover (huruf besar, huruf kecil, angka, dan simbol).',
            'password.confirmed' => 'Konfirmasi password tidak cocok dengan password yang dimasukkan.',
        ]);

        // Buat akun admin baru dengan status aktif
        $admin = Admin::create([
            'nama'                => $validated['name'],
            'email'               => $validated['email'],
            'password'            => Hash::make($validated['password']),
            'status'              => 'aktif',
            'password_changed_at' => now(),
        ]);

        // Login otomatis admin yang baru terdaftar
        Auth::login($admin);
        $request->session()->regenerate();

        // Catat riwayat login pertama
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

        $admin->forceFill([
            'last_login' => now(),
        ])->save();

        return redirect()->route('dashboard');
    }
}
