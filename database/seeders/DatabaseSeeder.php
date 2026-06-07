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
        // ── Owner account ─────────────────────────────────────────────────────
        User::query()->firstOrCreate(
            ['email' => 'owner@clinic.local'],
            [
                'name'     => 'Clinic Owner',
                'password' => 'password',
                'role'     => User::ROLE_OWNER,
            ]
        );

        // ── Default operatories ───────────────────────────────────────────────
        $operatories = [
            ['name' => 'Chair 1', 'color' => '#6366f1'],
            ['name' => 'Chair 2', 'color' => '#10b981'],
            ['name' => 'Chair 3', 'color' => '#f59e0b'],
            ['name' => 'X-Ray Room', 'color' => '#64748b'],
        ];

        foreach ($operatories as $data) {
            Operatory::query()->firstOrCreate(['name' => $data['name']], $data);
        }

        // ── Default appointment types ─────────────────────────────────────────
        $types = [
            ['name' => 'New Patient Exam',    'default_duration_minutes' => 60,  'color' => '#6366f1'],
            ['name' => 'Recall / Cleaning',   'default_duration_minutes' => 45,  'color' => '#10b981'],
            ['name' => 'Filling',             'default_duration_minutes' => 60,  'color' => '#f59e0b'],
            ['name' => 'Root Canal',          'default_duration_minutes' => 90,  'color' => '#ef4444'],
            ['name' => 'Crown Prep',          'default_duration_minutes' => 90,  'color' => '#8b5cf6'],
            ['name' => 'Extraction',          'default_duration_minutes' => 45,  'color' => '#ec4899'],
            ['name' => 'Consultation',        'default_duration_minutes' => 30,  'color' => '#14b8a6'],
            ['name' => 'Orthodontic Check',   'default_duration_minutes' => 30,  'color' => '#f97316'],
            ['name' => 'Implant Placement',   'default_duration_minutes' => 120, 'color' => '#0ea5e9'],
            ['name' => 'Emergency',           'default_duration_minutes' => 45,  'color' => '#dc2626'],
        ];

        foreach ($types as $data) {
            AppointmentType::query()->firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
