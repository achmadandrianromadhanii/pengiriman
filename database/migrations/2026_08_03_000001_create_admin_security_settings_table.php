<?php

// =========================================================================
// MIGRATION: Tabel admin_security_settings — Pengaturan keamanan per admin
// =========================================================================
// Fungsi: Menyimpan preferensi keamanan (toggle) admin ke database
//         agar tersinkron di semua perangkat.
// Kolom:
//   - admin_id          : FK ke tabel admins (unik, 1 admin = 1 setting)
//   - login_notification: Toggle notifikasi login baru (true/false)
//   - auto_logout       : Toggle auto logout saat idle (true/false)
//   - biometric_enabled : Toggle biometrik WebAuthn (true/false)
// [UPDATE: SECURITY V3] Menggantikan localStorage dengan database storage
// =========================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_security_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->unique()->constrained('admins')->cascadeOnDelete();
            $table->boolean('login_notification')->default(false);
            $table->boolean('auto_logout')->default(false);
            $table->boolean('biometric_enabled')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_security_settings');
    }
};
