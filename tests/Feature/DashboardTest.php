<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_can_be_rendered_for_authenticated_admin(): void
    {
        $admin = Admin::factory()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_dashboard_redirects_unauthenticated_user_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/login');
    }
}