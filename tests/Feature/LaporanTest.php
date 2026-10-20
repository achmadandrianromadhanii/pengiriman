<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_index_can_be_rendered(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin)->get('/laporan');
        $response->assertStatus(200);
    }
}