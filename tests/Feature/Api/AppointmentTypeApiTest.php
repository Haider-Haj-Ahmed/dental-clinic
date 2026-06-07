<?php

namespace Tests\Feature\Api;

use App\Models\AppointmentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AppointmentTypeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_appointment_type(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_OWNER]));

        $this->postJson('/api/v1/appointment-types', [
            'name'                     => 'Root Canal',
            'default_duration_minutes' => 90,
            'color'                    => '#ef4444',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Root Canal')
            ->assertJsonPath('data.default_duration_minutes', 90);
    }

    public function test_provider_cannot_create_appointment_type(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_PROVIDER]));

        $this->postJson('/api/v1/appointment-types', ['name' => 'Root Canal'])
            ->assertForbidden();
    }

    public function test_duration_must_be_at_least_5_minutes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_OWNER]));

        $this->postJson('/api/v1/appointment-types', [
            'name'                     => 'Quick Check',
            'default_duration_minutes' => 2,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('default_duration_minutes');
    }
}
