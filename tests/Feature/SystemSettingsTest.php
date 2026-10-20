<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_about_can_be_rendered(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin)->get('/system/about');
        $response->assertStatus(200);
    }

    public function test_system_cache_can_be_rendered(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin)->get('/system/cache');
        $response->assertStatus(200);
    }

    public function test_system_storage_can_be_rendered(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin)->get('/system/storage');
        $response->assertStatus(200);
    }
}