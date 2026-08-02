<?php

// =========================================================================
// MIGRATION: Tabel login_histories — Riwayat login admin
// =========================================================================
// Fungsi: Menyimpan setiap event login admin beserta metadata perangkat.
// Cara Kerja: Setiap kali admin login berhasil, satu baris baru di-insert
//             dengan informasi IP, user agent, browser, OS, dan device.
// Kolom:
//   - admin_id    : FK ke tabel admins
//   - ip_address  : Alamat IP pengguna saat login
//   - user_agent  : String user agent browser lengkap
//   - browser     : Nama browser (Chrome, Firefox, Safari, dll)
//   - os          : Sistem operasi (Windows 11, Android 15, macOS, dll)
//   - device_type : Jenis perangkat (Desktop, Mobile, Tablet)
//   - logged_in_at: Waktu login
// =========================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('os', 100)->nullable();
            $table->string('device_type', 20)->default('Desktop');
            $table->timestamp('logged_in_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_histories');
    }
};
