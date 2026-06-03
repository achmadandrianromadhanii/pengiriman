<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'nama_website',    'value' => 'SoftSend',                               'type' => 'text'],
            ['key' => 'alamat',         'value' => 'Jl. Sukun',              'type' => 'text'],
            ['key' => 'telepon',        'value' => '021-1234567',                            'type' => 'text'],
            ['key' => 'email',          'value' => 'admin@gmail.com',                      'type' => 'text'],
            ['key' => 'jam_operasional', 'value' => 'Senin–Sabtu 08.00–17.00',                'type' => 'text'],
        ];

        foreach ($settings as $s) {
            DB::table('settings')->updateOrInsert(
                ['key' => $s['key']],
                [
                    'value' => $s['value'],
                    'type' => $s['type'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
