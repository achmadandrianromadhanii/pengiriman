<?php

namespace App\Models;

// =========================================================================
// MODEL: AdminSecuritySetting — Pengaturan keamanan per admin
// =========================================================================
// Fungsi: Menyimpan preferensi toggle keamanan (notifikasi, auto logout,
//         biometrik) ke database agar tersinkron di semua perangkat.
// Cara Kerja:
//   - Relasi 1:1 dengan Admin (admin_id unique)
//   - Method static getOrCreate() untuk auto-create jika belum ada
//   - Menggantikan localStorage agar sinkron lintas perangkat
//   - Menggunakan Laravel Attribute accessor (get/set) untuk konversi
//     boolean <-> string agar kompatibel dengan Neon PostgreSQL.
// [UPDATE: SECURITY V6 — FIX DIRTY CHECK & NEON BOOLEAN]
// =========================================================================

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSecuritySetting extends Model
{
    // ── Tabel ────────────────────────────────────────────────────────────
    protected $table = 'admin_security_settings';

    // ── Kolom yang boleh diisi massal ────────────────────────────────────
    protected $fillable = [
        'admin_id',
        'login_notification',
        'auto_logout',
        'biometric_enabled',
    ];

    // ── Casting ──────────────────────────────────────────────────────────
    protected $casts = [
        'login_notification' => \App\Casts\PostgresBoolean::class,
        'auto_logout' => \App\Casts\PostgresBoolean::class,
        'biometric_enabled' => \App\Casts\PostgresBoolean::class,
    ];

    // ── Relasi: Setting ini milik admin mana ────────────────────────────
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    // =====================================================================
    // STATIC: Ambil atau buat setting untuk admin tertentu
    // =====================================================================
    // Fungsi: Memastikan setiap admin selalu punya 1 record setting.
    // Cara Kerja: Cari by admin_id, jika tidak ada buat baru dengan
    //             semua toggle default false.
    // =====================================================================
    public static function getOrCreate(int $adminId): self
    {
        $setting = self::where('admin_id', $adminId)->first();
        if (!$setting) {
            $setting = new self();
            $setting->admin_id = $adminId;
            $setting->login_notification = false;
            $setting->auto_logout = false;
            $setting->biometric_enabled = false;
            $setting->save();
        }
        return $setting;
    }
}
