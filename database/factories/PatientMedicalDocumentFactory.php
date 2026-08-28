<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\PatientMedicalDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PatientMedicalDocument> */
class PatientMedicalDocumentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id'            => Patient::factory(),
            'medical_case_id'       => null,
            'provider_id'           => null,
            'document_type'         => fake()->randomElement(PatientMedicalDocument::TYPES),
            'title'                 => fake()->sentence(3),
            'description'           => fake()->optional()->paragraph(),
            'file_path'             => 'medical-documents/' . fake()->uuid() . '.pdf',
            'file_size_kb'          => fake()->numberBetween(50, 5000),
            'mime_type'             => 'application/pdf',
            'taken_at'              => fake()->optional()->dateTimeBetween('-5 years', 'now')?->format('Y-m-d'),
            'external_source'       => null,
            'is_visible_to_patient' => false,
            'uploaded_by'           => User::factory(),
        ];
    }

    public function xray(): static
    {
        return $this->state([
            'document_type' => 'xray',
            'mime_type'     => 'image/jpeg',
            'file_path'     => 'medical-documents/' . fake()->uuid() . '.jpg',
        ]);
    }

    public function forPatient(Patient $patient): static
    {
        return $this->state(['patient_id' => $patient->id]);
    }

    public function uploadedBy(User $user): static
    {
        return $this->state(['uploaded_by' => $user->id]);
    }
}
