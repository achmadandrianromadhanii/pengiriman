<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $admin = Admin::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $admin = Admin::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->patch('/profile', [
                'nama' => 'Test User',
                'email' => 'test@gmail.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $admin->refresh();

        $this->assertSame('Test User', $admin->nama);
        $this->assertSame('test@gmail.com', $admin->email);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        // Email verification is not implemented/required, but we can verify it doesn't break
        $admin = Admin::factory()->create();

        $response = $this
            ->actingAs($admin)
            ->patch('/profile', [
                'nama' => 'Test User',
                'email' => $admin->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertSame('Test User', $admin->refresh()->nama);
    }

    public function test_user_can_delete_their_account(): void
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this
            ->actingAs($admin)
            ->delete('/profile', [
                'password' => 'password123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($admin->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $admin = Admin::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this
            ->actingAs($admin)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($admin->fresh());
    }
}
