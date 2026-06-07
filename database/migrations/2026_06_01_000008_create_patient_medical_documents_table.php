<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medical_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();

            // Optional — a document can exist without being tied to a specific case
            $table->foreignId('medical_case_id')->nullable()
                  ->constrained('patient_medical_cases')->nullOnDelete();

            // The provider who owns/submitted this document (nullable for imported docs)
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();

            $table->string('document_type'); // xray, panoramic, lab_result, etc.
            $table->string('title');
            $table->text('description')->nullable();

            $table->string('file_path', 500);
            $table->unsignedInteger('file_size_kb')->default(0);
            $table->string('mime_type', 100);

            // When was the X-ray/photo taken — may differ from upload date
            $table->date('taken_at')->nullable();

            // Free-text provenance for imported docs
            $table->string('external_source')->nullable(); // "Dr. Khalil – City Hospital 2021"

            $table->boolean('is_visible_to_patient')->default(false)->index();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'document_type']);
            $table->index(['patient_id', 'medical_case_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medical_documents');
    }
};
