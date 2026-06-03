<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengiriman_barang', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->unsignedBigInteger('pengiriman_id');

            $table->string('nama_barang', 100);
            $table->decimal('berat_kg', 8, 2);

            $table->decimal('panjang_cm', 8, 2)->default(0);
            $table->decimal('lebar_cm', 8, 2)->default(0);
            $table->decimal('tinggi_cm', 8, 2)->default(0);

            // GENERATED ALWAYS AS (...) STORED
            $table->decimal('volume_cm3', 12, 2)
                ->storedAs('panjang_cm * lebar_cm * tinggi_cm');

            $table->string('keterangan', 255)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('pengiriman_id')->references('id')->on('pengiriman')->cascadeOnDelete();
            $table->index('pengiriman_id', 'idx_barang_pengiriman');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengiriman_barang');
    }
};
