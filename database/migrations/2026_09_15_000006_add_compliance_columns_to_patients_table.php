<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            // Scheduled deletion — set when patient is archived, purged after X days
            $table->timestamp('scheduled_deletion_at')->nullable()->after('deleted_at');

            // Purge audit trail — who requested the purge and when it was confirmed
            $table->timestamp('purge_requested_at')->nullable()->after('scheduled_deletion_at');
            $table->foreignId('purge_requested_by')->nullable()->constrained('users')->after('purge_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropForeign(['purge_requested_by']);
            $table->dropColumn(['scheduled_deletion_at', 'purge_requested_at', 'purge_requested_by']);
        });
    }
};
