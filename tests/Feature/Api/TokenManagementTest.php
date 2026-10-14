<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class TokenManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_tokens_returns_all_active_tokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('device-1');
        $user->createToken('device-2');
        $token = $user->createToken('device-3')->plainTextToken;

        $response = $this->withToken($token)
             ->getJson('/api/v1/auth/tokens')
             ->assertOk();

        $this->assertCount(3, $response->json('data'));
    }

    public function test_list_tokens_marks_current_token(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('my-device')->plainTextToken;

        $response = $this->withToken($token)
             ->getJson('/api/v1/auth/tokens')
             ->assertOk();

        $current = collect($response->json('data'))->firstWhere('is_current', true);
        $this->assertNotNull($current);
        $this->assertEquals('my-device', $current['name']);
    }

    public function test_revoke_specific_token(): void
    {
        $user   = User::factory()->create();
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2');

        $this->withToken($token1)
             ->deleteJson("/api/v1/auth/tokens/{$token2->accessToken->id}")
             ->assertOk();

        $this->assertNull(PersonalAccessToken::find($token2->accessToken->id));
    }

    public function test_cannot_revoke_another_users_token(): void
    {
        $user1  = User::factory()->create();
        $user2  = User::factory()->create();
        $token1 = $user1->createToken('device-1')->plainTextToken;
        $token2 = $user2->createToken('device-2');

        $this->withToken($token1)
             ->deleteJson("/api/v1/auth/tokens/{$token2->accessToken->id}")
             ->assertNotFound();
    }
}
