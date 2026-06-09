<?php

namespace Tests\Feature\Api;

use App\Models\Patient;
use App\Models\PatientMedicalCase;
use App\Models\PatientMedicalDocument;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PatientMedicalDocumentTest extends TestCase
{
    use RefreshDatabase;

    private Patient $patient;
    private User $owner;
    private User $providerUser;
    private Provider $provider;
    private User $assistant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->patient      = Patient::factory()->create();
        $this->owner        = User::factory()->create(['role' => User::ROLE_OWNER]);
        $this->providerUser = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        $this->provider     = Provider::factory()->create(['user_id' => $this->providerUser->id]);
        $this->assistant    = User::factory()->create(['role' => User::ROLE_ASSISTANT]);
    }

    // ── Upload ────────────────────────────────────────────────────────────────

    public function test_owner_can_upload_document(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents", [
            'file'          => UploadedFile::fake()->create('xray.pdf', 500, 'application/pdf'),
            'document_type' => 'xray',
            'title'         => 'Upper jaw X-ray',
        ])->assertCreated()
            ->assertJsonPath('data.document_type', 'xray')
            ->assertJsonPath('data.title', 'Upper jaw X-ray')
            ->assertJsonPath('data.patient_id', $this->patient->id);

        // file should be on disk
        $path = PatientMedicalDocument::first()->file_path;
        Storage::disk('local')->assertExists($path);
    }

    public function test_provider_auto_assigned_on_upload(): void
    {
        Sanctum::actingAs($this->providerUser);

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents", [
            'file'          => UploadedFile::fake()->create('panoramic.jpg', 300, 'image/jpeg'),
            'document_type' => 'panoramic',
            'title'         => 'Panoramic view',
        ])->assertCreated()
            ->assertJsonPath('data.provider_id', $this->provider->id);
    }

    public function test_assistant_can_upload_document(): void
    {
        Sanctum::actingAs($this->assistant);

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents", [
            'file'          => UploadedFile::fake()->create('report.pdf', 200, 'application/pdf'),
            'document_type' => 'lab_result',
            'title'         => 'Blood test results',
        ])->assertCreated();
    }

    public function test_document_can_be_linked_to_a_medical_case(): void
    {
        Sanctum::actingAs($this->owner);

        $case = PatientMedicalCase::factory()->create([
            'patient_id' => $this->patient->id,
            'created_by' => $this->owner->id,
        ]);

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents", [
            'file'            => UploadedFile::fake()->create('before.jpg', 100, 'image/jpeg'),
            'document_type'   => 'intraoral_photo',
            'title'           => 'Before treatment',
            'medical_case_id' => $case->id,
        ])->assertCreated()
            ->assertJsonPath('data.medical_case_id', $case->id);
    }

    public function test_external_source_can_be_recorded(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents", [
            'file'            => UploadedFile::fake()->create('old_xray.pdf', 800, 'application/pdf'),
            'document_type'   => 'xray',
            'title'           => '2019 X-ray from City Hospital',
            'external_source' => 'Dr. Khalil – City Dental 2019',
            'taken_at'        => '2019-05-10',
        ])->assertCreated()
            ->assertJsonPath('data.external_source', 'Dr. Khalil – City Dental 2019')
            ->assertJsonPath('data.taken_at', '2019-05-10');
    }

    public function test_invalid_document_type_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents", [
            'file'          => UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf'),
            'document_type' => 'selfie',
            'title'         => 'Patient selfie',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('document_type');
    }

    public function test_future_taken_at_rejected(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/v1/patients/{$this->patient->id}/documents", [
            'file'          => UploadedFile::fake()->create('scan.pdf', 100, 'application/pdf'),
            'document_type' => 'xray',
            'title'         => 'Future X-ray',
            'taken_at'      => now()->addYear()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('taken_at');
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_documents_for_patient(): void
    {
        Sanctum::actingAs($this->owner);

        PatientMedicalDocument::factory()->count(3)->create([
            'patient_id'  => $this->patient->id,
            'uploaded_by' => $this->owner->id,
        ]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/documents")
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_index_can_filter_by_document_type(): void
    {
        Sanctum::actingAs($this->owner);

        PatientMedicalDocument::factory()->create(['patient_id' => $this->patient->id, 'document_type' => 'xray',      'uploaded_by' => $this->owner->id]);
        PatientMedicalDocument::factory()->create(['patient_id' => $this->patient->id, 'document_type' => 'lab_result', 'uploaded_by' => $this->owner->id]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/documents?document_type=xray")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.document_type', 'xray');
    }

    public function test_index_can_filter_by_medical_case(): void
    {
        Sanctum::actingAs($this->owner);

        $case = PatientMedicalCase::factory()->create(['patient_id' => $this->patient->id, 'created_by' => $this->owner->id]);

        PatientMedicalDocument::factory()->create(['patient_id' => $this->patient->id, 'medical_case_id' => $case->id, 'uploaded_by' => $this->owner->id]);
        PatientMedicalDocument::factory()->create(['patient_id' => $this->patient->id, 'medical_case_id' => null,      'uploaded_by' => $this->owner->id]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/documents?medical_case_id={$case->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ── Update metadata ───────────────────────────────────────────────────────

    public function test_uploader_can_update_document_metadata(): void
    {
        Sanctum::actingAs($this->providerUser);

        $document = PatientMedicalDocument::factory()->create([
            'patient_id'  => $this->patient->id,
            'uploaded_by' => $this->providerUser->id,
        ]);

        $this->putJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}", [
            'title'       => 'Updated title',
            'description' => 'Now with a description.',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated title');
    }

    public function test_non_uploader_provider_cannot_update_document(): void
    {
        $otherProvider = User::factory()->create(['role' => User::ROLE_PROVIDER]);
        Sanctum::actingAs($otherProvider);

        $document = PatientMedicalDocument::factory()->create([
            'patient_id'  => $this->patient->id,
            'uploaded_by' => $this->providerUser->id,
        ]);

        $this->putJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}", [
            'title' => 'Hijacked title',
        ])->assertForbidden();
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_uploader_can_delete_document_and_file_is_removed(): void
    {
        Sanctum::actingAs($this->owner);

        $file = UploadedFile::fake()->create('to_delete.pdf', 100, 'application/pdf');

        $response = $this->postJson("/api/v1/patients/{$this->patient->id}/documents", [
            'file'          => $file,
            'document_type' => 'other',
            'title'         => 'To be deleted',
        ])->assertCreated();

        $document = PatientMedicalDocument::first();

        $this->deleteJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('patient_medical_documents', ['id' => $document->id]);
        Storage::disk('local')->assertMissing($document->file_path);
    }

    // ── Download ──────────────────────────────────────────────────────────────

    public function test_download_returns_temporary_url(): void
    {
        Sanctum::actingAs($this->owner);

        $document = PatientMedicalDocument::factory()->create([
            'patient_id'  => $this->patient->id,
            'uploaded_by' => $this->owner->id,
            'file_path'   => 'medical-documents/'.$this->patient->id.'/test.pdf',
        ]);

        Storage::disk('local')->put($document->file_path, 'fake content');

        $this->getJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}/download")
            ->assertOk()
            ->assertJsonStructure(['url', 'expires_in', 'mime_type', 'title'])
            ->assertJsonPath('expires_in', 1800);
    }

    public function test_download_returns_404_when_file_missing_on_disk(): void
    {
        Sanctum::actingAs($this->owner);

        $document = PatientMedicalDocument::factory()->create([
            'patient_id'  => $this->patient->id,
            'uploaded_by' => $this->owner->id,
            'file_path'   => 'medical-documents/nonexistent.pdf',
        ]);

        // File is NOT put on the fake disk — simulates missing file
        $this->getJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}/download")
            ->assertNotFound();
    }

    // ── Cross-patient guard ───────────────────────────────────────────────────

    public function test_cannot_access_document_belonging_to_different_patient(): void
    {
        Sanctum::actingAs($this->owner);

        $otherPatient = Patient::factory()->create();
        $document = PatientMedicalDocument::factory()->create([
            'patient_id'  => $otherPatient->id,
            'uploaded_by' => $this->owner->id,
        ]);

        $this->getJson("/api/v1/patients/{$this->patient->id}/documents/{$document->id}")
            ->assertNotFound();
    }
}
