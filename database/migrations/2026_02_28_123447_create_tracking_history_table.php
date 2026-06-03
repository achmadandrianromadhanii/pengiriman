<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tracking_history', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->id();

            $table->unsignedBigInteger('pengiriman_id');
            $table->string('status_lama', 50)->nullable();
            $table->string('status_baru', 50);
            $table->string('lokasi', 200);
            $table->text('keterangan');
            $table->unsignedBigInteger('admin_id');

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('pengiriman_id')->references('id')->on('pengiriman')->cascadeOnDelete();
            $table->foreign('admin_id')->references('id')->on('admins')->restrictOnDelete();

            $table->index('pengiriman_id', 'idx_tracking_pengiriman');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracking_history');
    }
};
