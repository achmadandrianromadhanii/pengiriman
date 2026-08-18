<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // API untuk mengambil notifikasi (diminta via axios oleh frontend agar tidak merusak payload inertia)
    public function index(Request $request)
    {
        $admin = Auth::user();
        
        // Ambil 50 notifikasi terbaru
        $notifications = $admin->notifications()->take(50)->get()->map(function ($notif) {
            return [
                'id' => $notif->id,
                'data' => $notif->data,
                'read_at' => $notif->read_at ? $notif->read_at->toISOString() : null,
                'created_at' => $notif->created_at->toISOString(),
                'created_diff' => $notif->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $admin->unreadNotifications()->count()
        ]);
    }

    public function markAsRead($id)
    {
        $admin = Auth::user();
        $notification = $admin->notifications()->find($id);
        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json([
            'success' => true,
            'unread_count' => $admin->unreadNotifications()->count()
        ]);
    }

    public function markAllAsRead()
    {
        $admin = Auth::user();
        $admin->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0
        ]);
    }
}
