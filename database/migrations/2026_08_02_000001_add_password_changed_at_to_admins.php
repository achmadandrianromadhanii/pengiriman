<?php

// =========================================================================
// MIGRATION: Tambah kolom password_changed_at ke tabel admins
// =========================================================================
// Fungsi: Menyimpan timestamp kapan terakhir admin mengubah password.
// Cara Kerja: Kolom nullable timestamp, default null (belum pernah diubah).
// Tata Letak: Ditambahkan setelah kolom 'last_login' di tabel admins.
// =========================================================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->timestamp('password_changed_at')->nullable()->after('last_login');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('password_changed_at');
        });
    }
};
