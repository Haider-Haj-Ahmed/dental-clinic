<?php

namespace Database\Seeders;

use App\Models\AppointmentType;
use App\Models\Operatory;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // ── Owner account (email_verified_at set so 'verified' middleware passes) ──
        User::query()->updateOrCreate(
            ['email' => 'owner@clinic.local'],
            [
                'name'              => 'Dr. Haider Ahmed',
                'password'          => bcrypt('password'),
                'role'              => User::ROLE_OWNER,
                'email_verified_at' => now(),
            ]
        );

        // ── Provider account ──────────────────────────────────────────────────────
        User::query()->updateOrCreate(
            ['email' => 'provider@clinic.local'],
            [
                'name'              => 'Dr. Sarah Mansour',
                'password'          => bcrypt('password'),
                'role'              => User::ROLE_PROVIDER,
                'email_verified_at' => now(),
            ]
        );

        // ── Receptionist account ──────────────────────────────────────────────────
        User::query()->updateOrCreate(
            ['email' => 'reception@clinic.local'],
            [
                'name'              => 'Lina Aziz',
                'password'          => bcrypt('password'),
                'role'              => User::ROLE_RECEPTIONIST,
                'email_verified_at' => now(),
            ]
        );

        // ── Default operatories ───────────────────────────────────────────────────
        $operatories = [
            ['name' => 'Chair 1',   'color' => '#6366f1'],
            ['name' => 'Chair 2',   'color' => '#10b981'],
            ['name' => 'Chair 3',   'color' => '#f59e0b'],
            ['name' => 'X-Ray Room','color' => '#64748b'],
        ];
        foreach ($operatories as $data) {
            Operatory::query()->firstOrCreate(['name' => $data['name']], $data);
        }

        // ── Default appointment types ─────────────────────────────────────────────
        $types = [
            ['name' => 'New Patient Exam',  'default_duration_minutes' => 60, 'color' => '#6366f1'],
            ['name' => 'Recall / Cleaning', 'default_duration_minutes' => 45, 'color' => '#10b981'],
            ['name' => 'Filling',           'default_duration_minutes' => 60, 'color' => '#f59e0b'],
            ['name' => 'Root Canal',        'default_duration_minutes' => 90, 'color' => '#ef4444'],
            ['name' => 'Crown Prep',        'default_duration_minutes' => 90, 'color' => '#8b5cf6'],
            ['name' => 'Extraction',        'default_duration_minutes' => 45, 'color' => '#ec4899'],
            ['name' => 'Consultation',      'default_duration_minutes' => 30, 'color' => '#14b8a6'],
            ['name' => 'Orthodontics',      'default_duration_minutes' => 60, 'color' => '#f97316'],
            ['name' => 'Whitening',         'default_duration_minutes' => 60, 'color' => '#eab308'],
            ['name' => 'Emergency',         'default_duration_minutes' => 30, 'color' => '#dc2626'],
        ];
        foreach ($types as $data) {
            AppointmentType::query()->firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
