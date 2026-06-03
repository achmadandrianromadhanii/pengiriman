<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->updateOrInsert(
            ['email' => 'admin@gmail.com'],
            [
                'nama' => 'Rian Ganteng',
                'password' => Hash::make('admin122'),
                'foto' => null,
                'status' => 'aktif',
                'last_login' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
