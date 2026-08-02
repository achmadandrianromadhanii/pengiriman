<?php

namespace App\Models;

// =========================================================================
// MODEL: Admin — Pengguna administrator aplikasi
// =========================================================================
// Fungsi: Merepresentasikan admin yang login ke sistem.
// Relasi:
//   - hasMany Pengiriman (admin membuat pengiriman)
//   - hasMany TrackingHistory (admin mengupdate tracking)
//   - hasMany LoginHistory (riwayat login admin) [UPDATE: SECURITY MODULE]
// Field Baru:
//   - password_changed_at: Timestamp kapan password terakhir diubah
//   - last_login: Timestamp login terakhir
// =========================================================================

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Admin extends Authenticatable
{
    use HasFactory;

    protected $table = 'admins';

    // ── Kolom yang boleh diisi massal ────────────────────────────────────
    protected $fillable = [
        'nama',
        'email',
        'password',
        'foto',
        'status',
        'last_login',
        'password_changed_at', // [UPDATE: SECURITY] Kapan password terakhir diubah
    ];

    // ── Kolom tersembunyi dari serialisasi JSON ─────────────────────────
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // ── Cast tipe data ──────────────────────────────────────────────────
    protected $casts = [
        'last_login'          => 'datetime',
        'password_changed_at' => 'datetime', // [UPDATE: SECURITY]
        'password'            => 'hashed',
    ];

    // ── Accessor: Kompatibilitas nama ────────────────────────────────────
    public function getNameAttribute(): string
    {
        return $this->nama;
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['nama'] = $value;
    }

    // ── Relasi: Admin memiliki banyak pengiriman ────────────────────────
    public function pengiriman()
    {
        return $this->hasMany(Pengiriman::class, 'admin_id');
    }

    // ── Relasi: Admin memiliki banyak riwayat tracking ──────────────────
    public function trackingHistories()
    {
        return $this->hasMany(TrackingHistory::class, 'admin_id');
    }

    // =====================================================================
    // [UPDATE: SECURITY MODULE] Relasi: Admin memiliki riwayat login
    // =====================================================================
    // Fungsi: Mengakses semua record login_histories milik admin ini.
    // Cara Kerja: hasMany ke tabel login_histories via admin_id.
    // =====================================================================
    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class, 'admin_id');
    }
}
