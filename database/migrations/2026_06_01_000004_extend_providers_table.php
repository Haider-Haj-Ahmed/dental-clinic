<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->foreignId('operatory_id')->nullable()->after('user_id')
                  ->constrained()->nullOnDelete();
            $table->text('bio')->nullable()->after('license_number');
            $table->string('signature_path')->nullable()->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('operatory_id');
            $table->dropColumn(['bio', 'signature_path']);
        });
    }
};
