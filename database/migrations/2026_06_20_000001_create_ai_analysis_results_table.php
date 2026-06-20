<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();

            // What was analysed
            $table->string('source_type', 50);         // document, encounter, patient
            $table->unsignedBigInteger('source_id');   // FK to the source record

            // AI metadata — stored for liability and reproducibility
            $table->string('analysis_type', 50);       // xray_analysis, soap_suggestion, prescription_suggestion, perio_risk
            $table->string('ai_provider', 50);         // anthropic, openai, gemini
            $table->string('ai_model', 100);           // claude-haiku-4-5-20251001, gpt-4o, etc.

            // Input sent to AI (stored for audit)
            $table->text('input_summary')->nullable();

            // Structured output from AI
            $table->json('result');

            // Review state — provider must explicitly accept suggestions
            $table->string('status', 20)->default('pending'); // pending/accepted/dismissed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_notes')->nullable();

            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index(['patient_id', 'analysis_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_analysis_results');
    }
};
