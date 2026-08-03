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
// [UPDATE: SECURITY V3]
// =========================================================================

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

    // ── Cast tipe data (READING: DB → PHP boolean) ─────────────────────
    // Fungsi: Saat membaca dari database, konversi ke PHP boolean.
    protected $casts = [
        'login_notification' => 'boolean',
        'auto_logout'        => 'boolean',
        'biometric_enabled'  => 'boolean',
    ];

    // ── [FIX: NEON POSTGRESQL BOOLEAN] ───────────────────────────────────
    // Fungsi: Konversi boolean PHP ke string 'true'/'false' saat MENULIS.
    // Alasan: PDO mengirim PHP true/false sebagai integer 1/0, tapi
    //         Neon PostgreSQL menolak integer untuk kolom boolean.
    //         Mutator ini memastikan nilai dikirim sebagai string literal
    //         yang diterima PostgreSQL: 'true' atau 'false'.
    // Catatan: Tidak konflik dengan $casts karena:
    //   - $casts menangani READING (DB → PHP)
    //   - Mutator menangani WRITING (PHP → DB)
    public function setLoginNotificationAttribute($value): void
    {
        $this->attributes['login_notification'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function setAutoLogoutAttribute($value): void
    {
        $this->attributes['auto_logout'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function setBiometricEnabledAttribute($value): void
    {
        $this->attributes['biometric_enabled'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    // ── Relasi: Setting ini milik admin mana ────────────────────────────
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    // =====================================================================
    // STATIC: Ambil atau buat setting untuk admin tertentu
    // =====================================================================
    // Fungsi: Memastikan setiap admin selalu punya 1 record setting.
    // Cara Kerja: firstOrCreate() → ambil yang ada, atau buat baru dengan
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
