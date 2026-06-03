<?php

namespace App\Services;

use App\Models\Pengiriman;

class ResiService
{
    public function generate(): string
    {
        $tanggal = now()->format('ymd');
        $prefix = "SS-{$tanggal}-";

        // Wajib dipanggil di dalam DB::transaction()
        // agar lockForUpdate efektif mencegah race condition saat traffic tinggi.
        $count = Pengiriman::query()
            ->whereDate('created_at', today())
            ->lockForUpdate()
            ->count() + 1;

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
