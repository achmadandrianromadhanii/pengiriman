<?php

namespace App\Models;

// =========================================================================
// MODEL: LoginHistory — Riwayat login admin
// =========================================================================
// Fungsi: Merepresentasikan satu baris riwayat login di tabel login_histories.
// Cara Kerja: Setiap login berhasil, AuthenticatedSessionController membuat
//             record baru di tabel ini dengan metadata perangkat.
// Relasi: belongsTo Admin (admin_id → admins.id)
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
        // ── Deteksi Browser ──────────────────────────────────────────
        $browser = 'Unknown';
        if (preg_match('/Edg[e\/](\d+)/i', $ua))               $browser = 'Edge';
        elseif (preg_match('/OPR\/(\d+)/i', $ua))               $browser = 'Opera';
        elseif (preg_match('/Chrome\/(\d+)/i', $ua))             $browser = 'Chrome';
        elseif (preg_match('/Firefox\/(\d+)/i', $ua))            $browser = 'Firefox';
        elseif (preg_match('/Safari\/(\d+)/i', $ua) &&
                !str_contains($ua, 'Chrome'))                     $browser = 'Safari';

        // ── Deteksi OS ───────────────────────────────────────────────
        $os = 'Unknown';
        if (preg_match('/Windows NT 10\.0/i', $ua))              $os = 'Windows 10/11';
        elseif (preg_match('/Windows NT/i', $ua))                $os = 'Windows';
        elseif (preg_match('/Mac OS X (\d+[._]\d+)/i', $ua))    $os = 'macOS';
        elseif (preg_match('/Android (\d+)/i', $ua, $m))        $os = 'Android ' . $m[1];
        elseif (preg_match('/iPhone OS (\d+)/i', $ua, $m))      $os = 'iOS ' . $m[1];
        elseif (preg_match('/Linux/i', $ua))                     $os = 'Linux';

        // ── Deteksi Device Type ──────────────────────────────────────
        $device_type = 'Desktop';
        if (preg_match('/Mobile|Android|iPhone/i', $ua))         $device_type = 'Mobile';
        elseif (preg_match('/Tablet|iPad/i', $ua))               $device_type = 'Tablet';

        return compact('browser', 'os', 'device_type');
    }
}
