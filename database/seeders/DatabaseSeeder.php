<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $ownerPassword = config('app.seed_owner_password');

        if (! is_string($ownerPassword) || $ownerPassword === '') {
            throw new RuntimeException('SEED_OWNER_PASSWORD environment variable must be set as a non-empty value before running database seeding.');
        }

        User::query()->firstOrCreate(
            ['email' => 'owner@clinic.local'],
            [
                'name' => 'Clinic Owner',
                'password' => Hash::make($ownerPassword),
                'role' => User::ROLE_OWNER,
            ]
        );
    }
}
