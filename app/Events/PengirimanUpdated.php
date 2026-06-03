<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PengirimanUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $resi;

    public $status;

    public $lokasi;

    /**
     * Create a new event instance.
     */
    public function __construct($resi, $status = null, $lokasi = null)
    {
        $this->resi = $resi;
        $this->status = $status;
        $this->lokasi = $lokasi;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // Menggunakan public channel untuk tracking resi
        return [
            new Channel('pengiriman.'.$this->resi),
        ];
    }
}
