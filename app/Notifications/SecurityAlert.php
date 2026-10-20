<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class SecurityAlert extends Notification
{
    use Queueable;

    public $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable): array
    {
        return $this->data;
    }

    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'data' => $this->data,
            'created_at' => now()->toISOString(),
            'id' => $this->id,
        ]);
    }
}
