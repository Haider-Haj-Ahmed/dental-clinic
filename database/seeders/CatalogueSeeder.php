<?php

namespace Database\Seeders;

use App\Models\AppointmentType;
use App\Models\Operatory;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // ── Operatories ────────────────────────────────────────────
        $operatories = [
            ['name' => 'Chair 1',    'color' => '#6366f1', 'is_active' => true],
            ['name' => 'Chair 2',    'color' => '#10b981', 'is_active' => true],
            ['name' => 'Chair 3',    'color' => '#f59e0b', 'is_active' => true],
            ['name' => 'X-Ray Room', 'color' => '#64748b', 'is_active' => true],
            ['name' => 'Surgery',    'color' => '#ef4444', 'is_active' => true],
        ];

        foreach ($operatories as $data) {
            Operatory::query()->firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        // ── Appointment types ──────────────────────────────────────
        $types = [
            ['name' => 'New Patient Exam',    'default_duration_minutes' => 60, 'color' => '#6366f1'],
            ['name' => 'Recall / Cleaning',   'default_duration_minutes' => 45, 'color' => '#10b981'],
            ['name' => 'Filling',             'default_duration_minutes' => 60, 'color' => '#f59e0b'],
            ['name' => 'Root Canal',          'default_duration_minutes' => 90, 'color' => '#ef4444'],
            ['name' => 'Crown Prep',          'default_duration_minutes' => 90, 'color' => '#8b5cf6'],
            ['name' => 'Crown Delivery',      'default_duration_minutes' => 45, 'color' => '#a78bfa'],
            ['name' => 'Extraction',          'default_duration_minutes' => 45, 'color' => '#ec4899'],
            ['name' => 'Consultation',        'default_duration_minutes' => 30, 'color' => '#14b8a6'],
            ['name' => 'Orthodontics',        'default_duration_minutes' => 60, 'color' => '#f97316'],
            ['name' => 'Whitening',           'default_duration_minutes' => 60, 'color' => '#eab308'],
            ['name' => 'Implant Surgery',     'default_duration_minutes' => 120,'color' => '#dc2626'],
            ['name' => 'Implant Restoration', 'default_duration_minutes' => 60, 'color' => '#b91c1c'],
            ['name' => 'Emergency',           'default_duration_minutes' => 30, 'color' => '#991b1b'],
            ['name' => 'Pediatric Exam',      'default_duration_minutes' => 45, 'color' => '#0ea5e9'],
            ['name' => 'Denture',             'default_duration_minutes' => 60, 'color' => '#78716c'],
        ];

        foreach ($types as $data) {
            AppointmentType::query()->firstOrCreate(
                ['name' => $data['name']],
                $data
            );
        }
    }
}
