<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Services\SupabaseAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SupabaseAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_falls_back_to_local_password_auth_when_supabase_auth_fails(): void
    {
        config([
            'supabase.url' => 'https://example.supabase.co',
            'supabase.anon_key' => 'anon-key',
        ]);

        Http::fake([
            'https://example.supabase.co/auth/v1/token?grant_type=password' => Http::response([
                'error' => 'invalid_grant',
            ], 400),
        ]);

        $user = User::create([
            'name' => 'Local User',
            'email' => 'local@example.com',
            'password' => Hash::make('LocalPass123!'),
            'role' => Role::VehicleOwner->value,
            'is_active' => true,
        ]);

        $service = new SupabaseAuthService();
        $authenticatedUser = $service->attempt('local@example.com', 'LocalPass123!');

        $this->assertSame($user->id, $authenticatedUser->id);
    }
}
