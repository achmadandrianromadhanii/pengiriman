<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengiriman', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->string('nomor_resi', 25)->unique();

            $table->string('pengirim_nama', 100);
            $table->string('pengirim_hp', 20);
            $table->text('pengirim_alamat');
            $table->unsignedBigInteger('pengirim_kota_id');

            $table->string('penerima_nama', 100);
            $table->string('penerima_hp', 20);
            $table->text('penerima_alamat');
            $table->unsignedBigInteger('penerima_kota_id');

            $table->decimal('total_berat_kg', 10, 2)->default(0);
            $table->unsignedInteger('jumlah_barang')->default(1);

            $table->enum('jenis_layanan', ['reguler', 'express', 'kargo', 'ekonomi']);

            $table->decimal('biaya_pengiriman', 12, 2);
            $table->decimal('biaya_tambahan', 12, 2)->default(0);
            $table->decimal('biaya_asuransi', 12, 2)->default(0);
            $table->decimal('total_biaya', 12, 2)->default(0);

            $table->enum('metode_pembayaran', ['dibayar_pengirim', 'dibayar_penerima', 'cod']);
            $table->enum('status_pembayaran', ['lunas', 'belum_lunas'])->default('lunas');

            $table->enum('status', [
                'pending',
                'diproses',
                'dalam_perjalanan',
                'tiba_di_kota_tujuan',
                'sedang_diantar',
                'terkirim',
                'gagal',
                'dibatalkan',
            ])->default('pending');

            $table->date('estimasi_tiba');
            $table->timestamp('tanggal_terkirim')->nullable();

            $table->text('catatan')->nullable();
            $table->text('alasan_batal')->nullable();

            $table->unsignedBigInteger('admin_id');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('pengirim_kota_id')->references('id')->on('kota')->restrictOnDelete();
            $table->foreign('penerima_kota_id')->references('id')->on('kota')->restrictOnDelete();
            $table->foreign('admin_id')->references('id')->on('admins')->restrictOnDelete();

            $table->index('nomor_resi', 'idx_resi');
            $table->index('status', 'idx_status');
            $table->index('estimasi_tiba', 'idx_estimasi');
            $table->index('created_at', 'idx_created');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengiriman');
    }
};
