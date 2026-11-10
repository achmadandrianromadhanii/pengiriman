<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Seeder Admin:
     * Akun default seeder lama (admin@gmail.com / admin) dinonaktifkan dan dihapus kredensialnya
     * sesuai ketentuan sistem baru, di mana setiap pengguna wajib mendaftar akun baru (register)
     * atau login menggunakan Google / Facebook.
     */
    public function run(): void
    {
        // Nonaktifkan dan hilangkan akses akun default lama agar tidak bisa login dengan password 'admin'
        Admin::where('email', 'admin@gmail.com')->update([
            'status'   => 'nonaktif',
            'password' => '$2y$12$' . Str::random(53),
        ]);
    }
}
