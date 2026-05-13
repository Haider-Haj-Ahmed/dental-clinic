<?php

namespace Tests\Feature\Api;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_receptionist_can_create_patient(): void
    {
        $receptionist = User::factory()->create(['role' => User::ROLE_RECEPTIONIST]);
        Sanctum::actingAs($receptionist, ['*']);

        $this->postJson('/api/v1/patients', [
            'first_name' => 'Sarah',
            'last_name' => 'Ahmad',
            'phone' => '5551234',
            'email' => 'sarah@example.com',
        ])->assertCreated()
            ->assertJsonPath('data.first_name', 'Sarah')
            ->assertJsonPath('data.last_name', 'Ahmad');
    }

    public function test_provider_cannot_create_patient(): void
    {
        $providerUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($providerUser, ['*']);

        $this->postJson('/api/v1/patients', [
            'first_name' => 'Ali',
            'last_name' => 'Test',
        ])->assertForbidden();
    }

    public function test_provider_can_view_patient_list(): void
    {
        Patient::factory()->count(2)->create();

        $providerUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($providerUser, ['*']);

        $this->getJson('/api/v1/patients')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }
}
