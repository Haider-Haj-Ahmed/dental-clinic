<?php

namespace Tests\Feature\Api;

use App\Models\Operatory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperatoryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_operatory(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_OWNER]));

        $this->postJson('/api/v1/operatories', [
            'name'  => 'Chair 1',
            'color' => '#6366f1',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Chair 1');
    }

    public function test_receptionist_cannot_create_operatory(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_RECEPTIONIST]));

        $this->postJson('/api/v1/operatories', ['name' => 'Chair 1'])
            ->assertForbidden();
    }

    public function test_any_authenticated_user_can_list_operatories(): void
    {
        Operatory::factory()->count(3)->create();
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_PROVIDER]));

        $this->getJson('/api/v1/operatories')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_duplicate_operatory_name_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_OWNER]));
        Operatory::factory()->create(['name' => 'Chair 1']);

        $this->postJson('/api/v1/operatories', ['name' => 'Chair 1'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }
}
