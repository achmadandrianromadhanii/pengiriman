<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracking_search_page_can_be_rendered(): void
    {
        $response = $this->get('/tracking');
        $response->assertStatus(200);
    }
}