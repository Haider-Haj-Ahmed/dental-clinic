<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('other'); // emergency, spouse, parent, etc.
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('relationship')->nullable();
            $table->boolean('is_emergency')->default(false);
            $table->timestamps();
        });

        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('allergen');
            $table->string('reaction')->nullable();
            $table->string('severity')->nullable(); // mild / moderate / severe
            $table->foreignId('noted_by')->nullable()->constrained('providers')->nullOnDelete();
            $table->timestamp('noted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('condition');
            $table->string('icd_code', 20)->nullable();
            $table->string('status')->default('active')->index(); // active / resolved
            $table->date('onset_date')->nullable();
            $table->foreignId('noted_by')->nullable()->constrained('providers')->nullOnDelete();
            $table->timestamp('noted_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('drug_name');
            $table->string('dose')->nullable();
            $table->string('frequency')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('prescribed_by')->nullable()->constrained('providers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('consent_type');
            $table->timestamp('signed_at')->nullable();
            $table->boolean('signed_by_patient')->default(false);
            $table->foreignId('witness_provider_id')->nullable()->constrained('providers')->nullOnDelete();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_consents');
        Schema::dropIfExists('patient_medications');
        Schema::dropIfExists('patient_conditions');
        Schema::dropIfExists('patient_allergies');
        Schema::dropIfExists('patient_contacts');
    }
};
