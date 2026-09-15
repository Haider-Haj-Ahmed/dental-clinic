<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Encrypted TOTP secret (base32)
            $table->text('two_factor_secret')->nullable()->after('password');

            // Timestamp when the user confirmed their first OTP — 2FA is only
            // considered active when this is non-null
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_secret');

            // JSON array of one-time recovery codes (each hashed with bcrypt)
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_secret',
                'two_factor_confirmed_at',
                'two_factor_recovery_codes',
            ]);
        });
    }
};
