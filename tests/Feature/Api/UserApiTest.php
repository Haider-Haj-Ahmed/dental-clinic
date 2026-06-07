<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_user(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_OWNER]));

        $this->postJson('/api/v1/users', [
            'name'     => 'Jane Receptionist',
            'email'    => 'jane@clinic.local',
            'password' => 'password123',
            'role'     => User::ROLE_RECEPTIONIST,
        ])->assertCreated()
            ->assertJsonPath('data.role', User::ROLE_RECEPTIONIST);
    }

    public function test_receptionist_cannot_manage_users(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_RECEPTIONIST]));

        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_invalid_role_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_OWNER]));

        $this->postJson('/api/v1/users', [
            'name'     => 'Test',
            'email'    => 'test@test.com',
            'password' => 'password',
            'role'     => 'superadmin',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('role');
    }
}
