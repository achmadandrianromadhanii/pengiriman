<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.Admin.{id}', function ($user, $id) {
    // [UPDATE: BROADCASTING AUTHENTICATION FIX]
    // Fungsi: Memberikan izin (authorization) kepada Echo untuk terkoneksi ke channel private Admin.
    // Cara Kerja: Echo secara otomatis mengirim ID user yang sedang login.
    // Sistem mengecek apakah ID dari objek $user (Admin yang ter-autentikasi) sama dengan {id} dari channel.
    // Ditambahkan guards 'web' secara eksplisit untuk memastikan Laravel menggunakan sesi admin yang benar.
    return (int) $user->id === (int) $id;
}, ['guards' => ['web']]);
