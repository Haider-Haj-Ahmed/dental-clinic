<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreignId('appointment_type_id')->nullable()->after('provider_id')
                  ->constrained()->nullOnDelete();
            $table->foreignId('operatory_id')->nullable()->after('appointment_type_id')
                  ->constrained()->nullOnDelete();
            $table->string('color', 7)->nullable()->after('operatory_id');
            $table->timestamp('reminder_sent_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('appointment_type_id');
            $table->dropConstrainedForeignId('operatory_id');
            $table->dropColumn(['color', 'reminder_sent_at']);
        });
    }
};
