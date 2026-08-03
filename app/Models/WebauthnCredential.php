<?php

namespace App\Models;

// =========================================================================
// MODEL: WebauthnCredential — Kredensial biometrik WebAuthn/Passkeys
// =========================================================================
// Fungsi: Menyimpan public key credential dari WebAuthn API untuk login
//         tanpa password menggunakan sidik jari / face ID.
// Cara Kerja:
//   - Register: Browser → navigator.credentials.create() → simpan ke DB
//   - Login: Browser → navigator.credentials.get() → verifikasi di server
// Relasi: belongsTo Admin (admin_id → admins.id)
// [UPDATE: SECURITY V3] WebAuthn/Passkeys support
// =========================================================================

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebauthnCredential extends Model
{
    // ── Tabel ────────────────────────────────────────────────────────────
    protected $table = 'webauthn_credentials';

    // ── Kolom yang boleh diisi massal ────────────────────────────────────
    protected $fillable = [
        'admin_id',
        'credential_id',
        'public_key',
        'device_name',
        'sign_count',
    ];

    // ── Cast tipe data ──────────────────────────────────────────────────
    protected $casts = [
        'sign_count' => 'integer',
    ];

    // ── Relasi: Kredensial ini milik admin mana ─────────────────────────
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }
}
