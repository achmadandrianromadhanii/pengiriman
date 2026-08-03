<?php

// =========================================================================
// MIGRATION: Tabel webauthn_credentials — Kredensial biometrik WebAuthn
// =========================================================================
// Fungsi: Menyimpan public key credential yang didaftarkan via WebAuthn API.
// Cara Kerja:
//   1. Admin mengaktifkan biometrik di halaman Keamanan
//   2. Browser memanggil navigator.credentials.create() (Passkey)
//   3. Public key + credential ID disimpan di tabel ini
//   4. Saat login, browser memanggil navigator.credentials.get()
//   5. Server memverifikasi signature terhadap public key tersimpan
// Kolom:
//   - admin_id      : FK ke tabel admins
//   - credential_id : ID unik dari credential WebAuthn (base64url)
//   - public_key    : Public key credential (base64url encoded)
//   - device_name   : Nama perangkat yang mendaftarkan (dari UA parser)
// [UPDATE: SECURITY V3] WebAuthn/Passkeys support
// =========================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->text('credential_id');       // Base64url-encoded credential ID
            $table->text('public_key');           // Base64url-encoded public key
            $table->string('device_name', 255)->nullable(); // Nama perangkat pendaftar
            $table->unsignedInteger('sign_count')->default(0); // Counter anti-replay
            $table->timestamps();

            // Index untuk pencarian cepat saat login
            $table->index('credential_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webauthn_credentials');
    }
};
