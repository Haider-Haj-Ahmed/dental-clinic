<?php

namespace Tests\Feature\Api;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientMedicalCase;
use App\Models\PatientMedicalDocument;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientTimelineTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->patient = Patient::factory()->create();
        $this->owner   = User::factory()->create(['role' => User::ROLE_OWNER]);
    }

    public function test_returns_all_types_by_default(): void
    {
        Sanctum::actingAs($this->owner);
        $provider = Provider::factory()->create();

        Appointment::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $provider->id,
            'start_at'    => '2024-03-10 10:00:00',
            'end_at'      => '2024-03-10 10:45:00',
            'created_by'  => $this->owner->id,
        ]);

        PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'case_date'  => '2024-01-15',
            'created_by' => $this->owner->id,
        ]);

        PatientMedicalDocument::factory()->create([
            'patient_id'  => $this->patient->id,
            'uploaded_by' => $this->owner->id,
        ]);

        $response = $this->getJson("/api/v1/patients/{$this->patient->id}/timeline")
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['type', 'date', 'data']],
                'meta' => ['current_page', 'per_page', 'total', 'last_page', 'types'],
            ]);

        $types = collect($response->json('data'))->pluck('type')->unique()->sort()->values()->toArray();
        $this->assertContains('appointment', $types);
        $this->assertContains('medical_case', $types);
        $this->assertContains('document', $types);
    }

    public function test_items_are_sorted_newest_first(): void
    {
        Sanctum::actingAs($this->owner);
        $provider = Provider::factory()->create();

        PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'case_date'  => '2022-01-01',
            'created_by' => $this->owner->id,
        ]);

        PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'case_date'  => '2024-06-01',
            'created_by' => $this->owner->id,
        ]);

        PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'case_date'  => '2023-03-15',
            'created_by' => $this->owner->id,
        ]);

        $data = $this->getJson("/api/v1/patients/{$this->patient->id}/timeline?type[]=medical_case")
            ->assertOk()
            ->json('data');

        $dates = array_column($data, 'date');
        $sorted = $dates;
        rsort($sorted);

        $this->assertEquals($sorted, $dates);
    }

    public function test_can_filter_by_single_type(): void
    {
        Sanctum::actingAs($this->owner);
        $provider = Provider::factory()->create();

        PatientMedicalCase::factory()->create(['patient_id' => $this->patient->id, 'created_by' => $this->owner->id]);
        Appointment::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $provider->id,
            'start_at'    => now()->addDay(),
            'end_at'      => now()->addDay()->addMinutes(45),
            'created_by'  => $this->owner->id,
        ]);

        $data = $this->getJson("/api/v1/patients/{$this->patient->id}/timeline?type[]=medical_case")
            ->assertOk()
            ->json('data');

        $this->assertNotEmpty($data);
        foreach ($data as $item) {
            $this->assertEquals('medical_case', $item['type']);
        }
    }

    public function test_can_filter_by_multiple_types(): void
    {
        Sanctum::actingAs($this->owner);
        $provider = Provider::factory()->create();

        PatientMedicalCase::factory()->create(['patient_id' => $this->patient->id, 'created_by' => $this->owner->id]);
        PatientMedicalDocument::factory()->create(['patient_id' => $this->patient->id, 'uploaded_by' => $this->owner->id]);
        Appointment::factory()->create([
            'patient_id'  => $this->patient->id,
            'provider_id' => $provider->id,
            'start_at'    => now()->addDay(),
            'end_at'      => now()->addDay()->addMinutes(45),
            'created_by'  => $this->owner->id,
        ]);

        $data = $this->getJson("/api/v1/patients/{$this->patient->id}/timeline?type[]=medical_case&type[]=document")
            ->assertOk()
            ->json('data');

        $types = collect($data)->pluck('type')->unique()->sort()->values()->toArray();
        $this->assertContains('medical_case', $types);
        $this->assertContains('document', $types);
        $this->assertNotContains('appointment', $types);
    }

    public function test_can_filter_by_date_range(): void
    {
        Sanctum::actingAs($this->owner);

        PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'case_date'  => '2023-06-01',
            'created_by' => $this->owner->id,
        ]);

        PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'case_date'  => '2021-01-01',
            'created_by' => $this->owner->id,
        ]);

        $data = $this->getJson(
            "/api/v1/patients/{$this->patient->id}/timeline?type[]=medical_case&date_from=2023-01-01&date_to=2023-12-31"
        )->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertStringContainsString('2023', $data[0]['date']);
    }

    public function test_pagination_works(): void
    {
        Sanctum::actingAs($this->owner);

        PatientMedicalCase::factory()->count(5)->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->getJson(
            "/api/v1/patients/{$this->patient->id}/timeline?type[]=medical_case&per_page=2"
        )->assertOk();

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(5, $response->json('meta.total'));
        $this->assertEquals(3, $response->json('meta.last_page'));
    }

    public function test_page_two_returns_correct_items(): void
    {
        Sanctum::actingAs($this->owner);

        PatientMedicalCase::factory()->count(3)->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->owner->id,
        ]);

        $page1 = $this->getJson(
            "/api/v1/patients/{$this->patient->id}/timeline?type[]=medical_case&per_page=2&page=1"
        )->assertOk()->json('data');

        $page2 = $this->getJson(
            "/api/v1/patients/{$this->patient->id}/timeline?type[]=medical_case&per_page=2&page=2"
        )->assertOk()->json('data');

        $this->assertCount(2, $page1);
        $this->assertCount(1, $page2);

        $page1Ids = array_column(array_column($page1, 'data'), 'id');
        $page2Ids = array_column(array_column($page2, 'data'), 'id');
        $this->assertEmpty(array_intersect($page1Ids, $page2Ids));
    }

    public function test_invalid_type_is_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson("/api/v1/patients/{$this->patient->id}/timeline?type[]=lab_result")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type.0');
    }

    public function test_empty_timeline_returns_empty_data(): void
    {
        Sanctum::actingAs($this->owner);

        $response = $this->getJson("/api/v1/patients/{$this->patient->id}/timeline")
            ->assertOk();

        $this->assertCount(0, $response->json('data'));
        $this->assertEquals(0, $response->json('meta.total'));
    }

    public function test_assistant_can_view_timeline(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ASSISTANT]));

        $this->getJson("/api/v1/patients/{$this->patient->id}/timeline")
            ->assertOk();
    }

    public function test_unauthenticated_user_cannot_view_timeline(): void
    {
        $this->getJson("/api/v1/patients/{$this->patient->id}/timeline")
            ->assertUnauthorized();
    }
}
