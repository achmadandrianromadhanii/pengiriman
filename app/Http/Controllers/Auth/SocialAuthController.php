<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\LoginHistory;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Provider OAuth yang didukung
     */
    protected array $supportedProviders = ['google', 'facebook'];

    /**
     * Redirect pengguna ke halaman autentikasi OAuth provider
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (! in_array($provider, $this->supportedProviders, true)) {
            return redirect()->route('login')->withErrors([
                'email' => "Provider '{$provider}' tidak didukung.",
            ]);
        }

        // Cek apakah konfigurasi client_id dan secret sudah disetel
        $config = config("services.{$provider}");
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            return redirect()->route('login')->withErrors([
                'email' => "Kredensial OAuth " . ucfirst($provider) . " belum dikonfigurasi di server (.env).",
            ]);
        }

        try {
            return Socialite::driver($provider)->redirect();
        } catch (Exception $e) {
            Log::error("Socialite redirect error ({$provider}): " . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => "Gagal menghubungi layanan " . ucfirst($provider) . ": " . $e->getMessage(),
            ]);
        }
    }

    /**
     * Tangani callback dari OAuth provider
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, $this->supportedProviders, true)) {
            return redirect()->route('login')->withErrors([
                'email' => "Provider '{$provider}' tidak didukung.",
            ]);
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (Exception $e) {
            Log::error("Socialite callback error ({$provider}): " . $e->getMessage());
            return redirect()->route('login')->withErrors([
                'email' => "Autentikasi dengan " . ucfirst($provider) . " dibatalkan atau gagal.",
            ]);
        }

        $email = $socialUser->getEmail();
        $providerId = (string) $socialUser->getId();
        $name = $socialUser->getName() ?? $socialUser->getNickname() ?? ('Pengguna ' . ucfirst($provider));
        $avatar = $socialUser->getAvatar();

        if (empty($email)) {
            return redirect()->route('login')->withErrors([
                'email' => "Akun " . ucfirst($provider) . " tidak menyediakan alamat email yang valid.",
            ]);
        }

        // Cari admin berdasarkan provider_name + provider_id ATAU email
        $admin = Admin::query()
            ->where(function ($query) use ($provider, $providerId, $email) {
                $query->where([
                    'provider_name' => $provider,
                    'provider_id'   => $providerId,
                ])->orWhere('email', $email);
            })
            ->first();

        if ($admin) {
            // Update data provider & avatar jika belum ada
            $admin->update([
                'provider_name' => $provider,
                'provider_id'   => $providerId,
                'foto'          => $admin->foto ?: $avatar,
            ]);
        } else {
            // Buat admin baru secara otomatis
            $admin = Admin::create([
                'nama'          => $name,
                'email'         => $email,
                'password'      => null, // Akun sosmed tidak memiliki password lokal
                'foto'          => $avatar,
                'provider_name' => $provider,
                'provider_id'   => $providerId,
                'status'        => 'aktif',
            ]);
        }

        if ($admin->status !== 'aktif') {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda dinonaktifkan oleh administrator.',
            ]);
        }

        // Login akun admin
        Auth::login($admin, true);
        $request->session()->regenerate();

        // Catat riwayat login
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

        return redirect()->intended(route('dashboard'));
    }
}
