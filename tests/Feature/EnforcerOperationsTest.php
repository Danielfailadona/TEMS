<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnforcerOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_visit_team_zone_and_tracking_pages(): void
    {
        $user = User::factory()->create([
            'email' => 'ops@example.com',
            'password' => bcrypt('password'),
            'role' => 'administrator',
        ]);

        $this->actingAs($user)->get('/teams')->assertOk();
        $this->actingAs($user)->get('/zones')->assertOk();
        $this->actingAs($user)->get('/tracking')->assertOk();
    }
}
