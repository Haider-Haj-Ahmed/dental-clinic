<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('referring_provider_id')->constrained('providers');
            $table->foreignId('encounter_id')->nullable()->constrained()->nullOnDelete();

            // Who we're referring to
            $table->string('referred_to_name');
            $table->string('referred_to_specialty')->nullable();
            $table->string('referred_to_clinic')->nullable();
            $table->string('referred_to_email')->nullable();
            $table->string('referred_to_phone')->nullable();

            // Clinical content
            $table->text('reason');
            $table->text('clinical_notes')->nullable();
            $table->text('relevant_history')->nullable();
            $table->text('requested_action')->nullable();
            $table->string('urgency')->default('routine');  // routine | urgent | emergency

            // Tracking
            $table->date('referral_date');
            $table->date('appointment_date')->nullable();
            $table->boolean('is_printed')->default(false);
            $table->timestamp('printed_at')->nullable();
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
