<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Working hours (one row per day of week) ───────────────
        Schema::create('working_hours', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('day_of_week');  // 0=Sunday … 6=Saturday
            $table->time('open_time')->nullable();        // null when is_closed
            $table->time('close_time')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->unique('day_of_week');
        });

        // ── Clinic closures (holidays, one-off closures) ──────────
        Schema::create('clinic_closures', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('reason')->nullable();
            $table->boolean('all_day')->default(true);
            $table->time('start_time')->nullable();   // used when all_day = false
            $table->time('end_time')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique('date');   // one closure entry per date
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_closures');
        Schema::dropIfExists('working_hours');
    }
};
