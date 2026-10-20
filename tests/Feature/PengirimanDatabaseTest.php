<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PengirimanDatabaseTest extends TestCase
{
    use RefreshDatabase;

    /**
     * =========================================================================
     * DATABASE TEST: Memastikan Factory & Seeder Database Berjalan
     * =========================================================================
     */
    public function test_admin_can_be_created_in_database(): void
    {
        $admin = Admin::factory()->create([
            'email' => 'testdb@example.com'
        ]);

        $this->assertDatabaseHas('admins', [
            'email' => 'testdb@example.com'
        ]);
    }
}