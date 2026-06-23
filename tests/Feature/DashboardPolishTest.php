<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPolishTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_dashboard_shows_recent_activity_and_quick_actions(): void
    {
        $user = User::factory()->create([
            'email' => 'ops@example.com',
            'password' => bcrypt('password'),
            'role' => 'administrator',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Quick actions');
        $response->assertSee('Recent activity');
    }
}
