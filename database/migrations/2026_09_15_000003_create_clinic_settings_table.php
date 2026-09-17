<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinic_settings', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('clinic_name')->default('Crystalline Dental');
            $table->string('clinic_email')->nullable();
            $table->string('clinic_phone')->nullable();
            $table->string('clinic_address')->nullable();
            $table->string('clinic_logo_path')->nullable();
            $table->string('website')->nullable();

            // Locale & display
            $table->string('timezone')->default('Asia/Damascus');
            $table->string('currency_code', 3)->default('SYP');
            $table->string('currency_symbol', 10)->default('SYP');
            $table->string('date_format')->default('d M Y');
            $table->string('time_format')->default('H:i');
            $table->string('language', 5)->default('en');

            // Branding
            $table->string('primary_color', 7)->default('#4fdbcc');

            // Billing
            $table->string('tax_name')->default('VAT');
            $table->unsignedTinyInteger('tax_rate')->default(0);   // percentage
            $table->string('invoice_prefix', 10)->default('INV-');
            $table->unsignedInteger('invoice_starting_number')->default(1);

            // Appointments
            $table->unsignedSmallInteger('appointment_slot_minutes')->default(15);
            $table->unsignedSmallInteger('cancellation_policy_hours')->default(24);

            // Reminders
            $table->unsignedSmallInteger('reminder_first_hours')->default(24);
            $table->unsignedSmallInteger('reminder_second_hours')->default(2);
            $table->boolean('reminder_sms_enabled')->default(false);
            $table->boolean('reminder_email_enabled')->default(true);
            $table->boolean('reminder_whatsapp_enabled')->default(false);

            // Security
            $table->boolean('require_2fa')->default(false);
            $table->unsignedSmallInteger('session_timeout_minutes')->default(480);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_settings');
    }
};
