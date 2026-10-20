<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TarifTest extends TestCase
{
    use RefreshDatabase;

    public function test_tarif_index_can_be_rendered(): void
    {
        $admin = Admin::factory()->create();
        $response = $this->actingAs($admin)->get('/tarif');
        $response->assertStatus(200);
    }
}