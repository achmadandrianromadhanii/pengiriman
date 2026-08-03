<?php

namespace App\Models;

// =========================================================================
// MODEL: LoginHistory — Riwayat login admin
// =========================================================================
// Fungsi: Merepresentasikan satu baris riwayat login di tabel login_histories.
// Cara Kerja: Setiap login berhasil, AuthenticatedSessionController membuat
//             record baru di tabel ini dengan metadata perangkat.
// Relasi: belongsTo Admin (admin_id → admins.id)
// [UPDATE: SECURITY V2] Menambahkan parseDeviceName() untuk mendeteksi
//   vendor dan model perangkat dari User-Agent string.
// =========================================================================

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginHistory extends Model
{
    // ── Tabel & Timestamps ───────────────────────────────────────────────
    protected $table = 'login_histories';
    public $timestamps = false; // Hanya pakai logged_in_at, bukan created_at/updated_at

    // ── Kolom yang boleh diisi massal ────────────────────────────────────
    protected $fillable = [
        'admin_id',
        'ip_address',
        'user_agent',
        'browser',
        'os',
        'device_type',
        'logged_in_at',
    ];

    // ── Cast tipe data ──────────────────────────────────────────────────
    protected $casts = [
        'logged_in_at' => 'datetime',
    ];

    // ── Relasi: Riwayat ini milik admin mana ────────────────────────────
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    // =====================================================================
    // STATIC: Parse User Agent → Browser, OS, Device Type
    // =====================================================================
    // Fungsi: Mengekstrak informasi browser, OS, dan jenis perangkat
    //         dari string User-Agent HTTP.
    // Cara Kerja: Regex matching terhadap pola umum user agent.
    // Return: ['browser' => string, 'os' => string, 'device_type' => string]
    // =====================================================================
    public static function parseUserAgent(string $ua): array
    {
        // ── Deteksi Browser + Versi ──────────────────────────────────
        $browser = 'Unknown';
        if (preg_match('/Edg[e\/](\d+)/i', $ua, $m))            $browser = 'Edge ' . $m[1];
        elseif (preg_match('/OPR\/(\d+)/i', $ua, $m))            $browser = 'Opera ' . $m[1];
        elseif (preg_match('/Chrome\/(\d+)/i', $ua, $m))          $browser = 'Chrome ' . $m[1];
        elseif (preg_match('/Firefox\/(\d+)/i', $ua, $m))         $browser = 'Firefox ' . $m[1];
        elseif (preg_match('/Safari\/(\d+)/i', $ua) &&
                !str_contains($ua, 'Chrome'))                      $browser = 'Safari';

        // ── Deteksi OS ───────────────────────────────────────────────
        $os = 'Unknown';
        if (preg_match('/Windows NT 10\.0/i', $ua))               $os = 'Windows 10/11';
        elseif (preg_match('/Windows NT/i', $ua))                 $os = 'Windows';
        elseif (preg_match('/Mac OS X (\d+[._]\d+)/i', $ua))     $os = 'macOS';
        elseif (preg_match('/Android (\d+)/i', $ua, $m))         $os = 'Android ' . $m[1];
        elseif (preg_match('/iPhone OS (\d+)/i', $ua, $m))       $os = 'iOS ' . $m[1];
        elseif (preg_match('/Linux/i', $ua))                      $os = 'Linux';

        // ── Deteksi Device Type ──────────────────────────────────────
        $device_type = 'Desktop';
        if (preg_match('/Mobile|Android|iPhone/i', $ua) &&
            !preg_match('/Tablet|iPad/i', $ua))                   $device_type = 'Mobile';
        elseif (preg_match('/Tablet|iPad/i', $ua))                $device_type = 'Tablet';

        return compact('browser', 'os', 'device_type');
    }

    // =====================================================================
    // STATIC: Parse Device Name — Vendor & Model dari User-Agent
    // =====================================================================
    // Fungsi: Mendeteksi nama perangkat (vendor + model) dari UA string.
    // Cara Kerja:
    //   1. Cek pola Build/ (Android) untuk mengekstrak model perangkat
    //   2. Cek pola vendor terkenal (Samsung, OPPO, Xiaomi, dll.)
    //   3. Fallback ke nama OS generik jika tidak terdeteksi
    // Return: string nama perangkat (mis. "OPPO Reno 12F", "Samsung SM-S926B")
    // =====================================================================
    public static function parseDeviceName(string $ua): string
    {
        // ── iPhone / iPad ────────────────────────────────────────────
        if (preg_match('/iPhone/i', $ua))                         return 'iPhone';
        if (preg_match('/iPad/i', $ua))                           return 'iPad';

        // ── Android: Coba ambil model dari pola "Build/" ─────────────
        // Format umum: ...device_model Build/...
        if (preg_match('/;\s*([^;)]+)\s*Build\//i', $ua, $m)) {
            $model = trim($m[1]);
            // Hapus prefix "Linux; Android X;" jika ada
            $model = preg_replace('/^(Linux;\s*)?Android\s*\d+(\.\d+)*;\s*/i', '', $model);
            if (!empty($model) && strlen($model) > 1) {
                return $model;
            }
        }

        // ── Deteksi vendor Android populer dari UA ───────────────────
        $vendors = [
            'Samsung'  => '/Samsung[\/\s]?([A-Za-z0-9\-]+)/i',
            'OPPO'     => '/OPPO[\/\s]?([A-Za-z0-9\-]+)/i',
            'Xiaomi'   => '/Xiaomi[\/\s]?([A-Za-z0-9\-]+)/i',
            'Redmi'    => '/Redmi[\/\s]?([A-Za-z0-9\-]+)/i',
            'Vivo'     => '/vivo[\/\s]?([A-Za-z0-9\-]+)/i',
            'Realme'   => '/Realme[\/\s]?([A-Za-z0-9\-]+)/i',
            'Huawei'   => '/Huawei[\/\s]?([A-Za-z0-9\-]+)/i',
            'OnePlus'  => '/OnePlus[\/\s]?([A-Za-z0-9\-]+)/i',
        ];

        foreach ($vendors as $vendor => $pattern) {
            if (preg_match($pattern, $ua, $m)) {
                return $vendor . ' ' . ($m[1] ?? '');
            }
        }

        // ── Desktop fallback ────────────────────────────────────────
        if (preg_match('/Macintosh/i', $ua))                      return 'Mac';
        if (preg_match('/Windows/i', $ua))                        return 'Windows PC';
        if (preg_match('/Linux/i', $ua))                          return 'Linux PC';

        return 'Perangkat Tidak Dikenal';
    }
}
