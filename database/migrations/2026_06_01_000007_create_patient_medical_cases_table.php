<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_medical_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();

            // The provider who recorded this case (null if purely external/historical)
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();

            // External clinic info — filled when is_external = true
            $table->string('previous_clinic')->nullable();
            $table->string('previous_dentist')->nullable();

            $table->date('case_date');
            $table->string('case_type'); // examination, extraction, filling, etc.
            $table->string('chief_complaint', 500)->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('treatment_performed')->nullable();
            $table->text('outcome')->nullable();

            // true = this case was done at another clinic (patient brought their history)
            $table->boolean('is_external')->default(false)->index();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'case_date']);
            $table->index(['patient_id', 'is_external']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_medical_cases');
    }
};
