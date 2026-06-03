<?php

namespace App\Services;

use App\Models\Pengiriman;

class ResiService
{
    public function generate(): string
    {
        $tanggal = now()->format('ymd');
        $prefix = "SS-{$tanggal}-";

        // ── [UPDATE: OPTIMASI POSTGRESQL & PERFORMA] ─────────────
        // Fungsi: Mengambil record resi terakhir hari ini untuk di-lock dan dinaikkan counternya.
        // Penjelasan: PostgreSQL melarang penggunaan `lockForUpdate()` pada fungsi agregat seperti `count()`.
        // Solusi ini jauh lebih cerdas dan kencang (O(1)): kita hanya memanggil 1 baris terakhir (first),
        // menguncinya (lockForUpdate), lalu mengekstrak angka terakhirnya. Ini menyelesaikan error
        // sekaligus membuat performa LCP/Backend response jauh lebih "Ngebut" karena tidak perlu menghitung (count) seluruh baris.
        $latest = Pengiriman::query()
            ->where('nomor_resi', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $count = $latest ? ((int) substr($latest->nomor_resi, -4)) + 1 : 1;

        $resi = $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);

        // Safety loop: jika tetap ada duplicate (mis. data legacy / manual insert),
        // naikkan counter sampai dapat resi yang benar-benar unik.
        while (Pengiriman::query()->where('nomor_resi', $resi)->exists()) {
            $count++;
            $resi = $prefix.str_pad((string) $count, 4, '0', STR_PAD_LEFT);
        }

        return $resi;
    }
}
