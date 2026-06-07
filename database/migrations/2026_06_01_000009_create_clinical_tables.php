<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Encounters (visit headers)
        Schema::create('encounters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->date('encounter_date')->index();
            $table->text('subjective')->nullable();  // S — what the patient says
            $table->text('objective')->nullable();   // O — clinical findings
            $table->text('assessment')->nullable();  // A — diagnosis
            $table->text('plan')->nullable();        // P — treatment plan
            $table->boolean('is_locked')->default(false)->index();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['patient_id', 'encounter_date']);
        });

        // Odontogram entries (tooth/surface conditions and procedures)
        Schema::create('odontogram_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('tooth_number'); // FDI notation: 11–48
            $table->string('surface', 10)->nullable();   // M, D, O, B, L, all
            $table->string('entry_type', 20);            // condition / procedure
            $table->string('code', 20)->nullable();
            $table->string('color_hex', 7)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['patient_id', 'tooth_number']);
        });

        // Perio exams
        Schema::create('perio_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->date('exam_date')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Perio measurements (6 sites per tooth × 32 teeth = up to 192 rows per exam)
        Schema::create('perio_measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perio_exam_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('tooth_number');
            $table->string('site', 5); // MB, B, DB, ML, L, DL
            $table->unsignedTinyInteger('probing_depth')->nullable();
            $table->tinyInteger('recession')->nullable(); // can be negative
            $table->boolean('bleeding_on_probe')->default(false);
            $table->unsignedTinyInteger('furcation')->default(0); // 0–3
            $table->unsignedTinyInteger('mobility')->default(0);  // 0–3
            $table->boolean('suppuration')->default(false);
            $table->timestamps();

            $table->index(['perio_exam_id', 'tooth_number']);
        });

        // Treatment plans
        Schema::create('treatment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('status')->default('draft')->index(); // draft/presented/accepted/rejected
            $table->decimal('total_fee', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('presented_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('treatment_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('procedure_code_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('tooth_number')->nullable();
            $table->string('surface', 10)->nullable();
            $table->string('description');
            $table->decimal('fee', 10, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status')->default('pending'); // pending/completed/cancelled
            $table->timestamps();
        });

        // Prescriptions
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained()->restrictOnDelete();
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('issued_at');
            $table->text('notes')->nullable();
            $table->boolean('is_printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->string('drug_name');
            $table->string('dose')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->text('instructions')->nullable();
            $table->string('quantity')->nullable();
            $table->timestamps();
        });

        // Recalls
        Schema::create('recalls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('due_date')->index();
            $table->string('status')->default('pending')->index(); // pending/sent/booked/dismissed
            $table->timestamp('last_reminder_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recalls');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('treatment_plan_items');
        Schema::dropIfExists('treatment_plans');
        Schema::dropIfExists('perio_measures');
        Schema::dropIfExists('perio_exams');
        Schema::dropIfExists('odontogram_entries');
        Schema::dropIfExists('encounters');
    }
};
