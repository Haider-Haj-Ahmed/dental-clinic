<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_get_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'owner@clinic.test',
            'password' => 'password',
            'role' => User::ROLE_OWNER,
        ]);

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone-15',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure(['token', 'token_type', 'abilities', 'user' => ['id', 'email', 'role']]);

        $token = $loginResponse->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'owner@clinic.test',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'owner@clinic.test',
            'password' => 'wrong-password',
            'device_name' => 'Android',
        ])->assertStatus(422);
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create([
            'password' => 'password',
            'role' => User::ROLE_RECEPTIONIST,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'Pixel',
        ])->json('token');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertNull(PersonalAccessToken::findToken($token));
    }
}
