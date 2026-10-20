<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengirimanTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengiriman_index_can_be_rendered(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin)->get('/pengiriman');
        $response->assertStatus(200);
    }

    public function test_pengiriman_create_can_be_rendered(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin)->get('/pengiriman/create');
        $response->assertStatus(200);
    }
}