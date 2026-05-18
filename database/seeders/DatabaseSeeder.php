<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $ownerPassword = env('SEED_OWNER_PASSWORD');

        if (! is_string($ownerPassword) || $ownerPassword === '') {
            $ownerPassword = Str::random(40);
        }

        User::query()->firstOrCreate(
            ['email' => 'owner@clinic.local'],
            [
                'name' => 'Clinic Owner',
                'password' => $ownerPassword,
                'role' => User::ROLE_OWNER,
            ]
        );
    }
}
