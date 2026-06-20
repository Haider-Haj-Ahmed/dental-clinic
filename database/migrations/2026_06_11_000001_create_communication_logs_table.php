<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('recall_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel');          // email / sms / whatsapp
            $table->string('direction');        // out / in
            $table->string('subject')->nullable();
            $table->text('body_preview')->nullable();
            $table->string('status');           // queued / sent / delivered / failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['patient_id', 'created_at']);
            $table->index(['recall_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
