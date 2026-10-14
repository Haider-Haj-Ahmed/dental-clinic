<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // OTP codes for passwordless patient portal login
        Schema::create('patient_otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('code', 6);                      // 6-digit OTP
            $table->string('channel')->default('email');    // email | sms
            $table->timestamp('expires_at');
            $table->boolean('used')->default(false);
            $table->timestamps();

            $table->index(['patient_id', 'used', 'expires_at']);
        });

        // Personal access tokens for patients (separate from staff tokens)
        // Uses the same personal_access_tokens table but with tokenable_type = Patient
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_otp_codes');
    }
};
