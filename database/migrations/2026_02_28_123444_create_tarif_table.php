<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            // Tarif bersifat universal berdasarkan jenis layanan
            $table->enum('jenis_layanan', ['reguler', 'express', 'kargo', 'ekonomi'])->unique();

            // Komponen perhitungan tarif
            $table->decimal('harga_dasar', 12, 2)->default(0)->comment('Biaya dasar / minimum');
            $table->decimal('harga_per_km', 12, 2)->default(0)->comment('Biaya tambahan per kilometer jarak');
            $table->decimal('harga_per_kg', 12, 2)->default(0)->comment('Biaya tambahan per kilogram berat');

            $table->unsignedInteger('estimasi_hari');
            $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('jenis_layanan', 'idx_layanan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif');
    }
};
