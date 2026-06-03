<?php

namespace App\Observers;

use App\Events\DashboardUpdated;
use App\Models\Pengiriman;
use Illuminate\Support\Facades\Cache;

class PengirimanObserver
{
    private function triggerDashboardUpdate()
    {
        // Hapus cache statistik agar dashboard mengambil data baru
        Cache::forget('dashboard.stats');

        // Broadcast event ke websocket
        event(new DashboardUpdated);
    }

    /**
     * Handle the Pengiriman "created" event.
     */
    public function created(Pengiriman $pengiriman): void
    {
        $this->triggerDashboardUpdate();
    }

    /**
     * Handle the Pengiriman "updated" event.
     */
    public function updated(Pengiriman $pengiriman): void
    {
        $this->triggerDashboardUpdate();
    }

    /**
     * Handle the Pengiriman "deleted" event.
     */
    public function deleted(Pengiriman $pengiriman): void
    {
        $this->triggerDashboardUpdate();
    }

    /**
     * Handle the Pengiriman "restored" event.
     */
    public function restored(Pengiriman $pengiriman): void
    {
        $this->triggerDashboardUpdate();
    }

    /**
     * Handle the Pengiriman "force deleted" event.
     */
    public function forceDeleted(Pengiriman $pengiriman): void
    {
        $this->triggerDashboardUpdate();
    }
}
