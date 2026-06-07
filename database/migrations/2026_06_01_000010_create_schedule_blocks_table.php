<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('operatory_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('start_at');
            $table->dateTime('end_at');
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['provider_id', 'start_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_blocks');
    }
};
