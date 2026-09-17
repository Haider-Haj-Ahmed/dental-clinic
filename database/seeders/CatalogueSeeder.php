<?php

namespace Database\Seeders;

use App\Models\AppointmentType;
use App\Models\ClinicSetting;
use App\Models\WorkingHour;
use App\Models\Operatory;
use Illuminate\Database\Seeder;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // ── Clinic settings (singleton — one row only) ─────────────────
        ClinicSetting::firstOrCreate([], [
            'clinic_name'               => env('APP_CLINIC_NAME', 'Crystalline Dental'),
            'clinic_email'              => env('MAIL_FROM_ADDRESS'),
            'timezone'                  => env('APP_TIMEZONE', 'Asia/Damascus'),
            'currency_code'             => 'SYP',
            'currency_symbol'           => 'SYP',
            'language'                  => 'en',
            'primary_color'             => '#4fdbcc',
            'tax_name'                  => 'VAT',
            'tax_rate'                  => 0,
            'invoice_prefix'            => 'INV-',
            'invoice_starting_number'   => 1,
            'appointment_slot_minutes'  => 15,
            'cancellation_policy_hours' => 24,
            'reminder_first_hours'      => 24,
            'reminder_second_hours'     => 2,
            'reminder_email_enabled'    => true,
            'reminder_sms_enabled'      => false,
            'reminder_whatsapp_enabled' => false,
            'require_2fa'               => false,
            'session_timeout_minutes'   => 480,
        ]);

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

        // ── Working hours (Sun–Sat, clinic open Sat–Thu) ───────────────
        $defaultHours = [
            0 => ['is_closed' => true,  'open_time' => null,    'close_time' => null],    // Sunday
            1 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'], // Monday
            2 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'], // Tuesday
            3 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'], // Wednesday
            4 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'], // Thursday
            5 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '14:00'], // Friday (half day)
            6 => ['is_closed' => false, 'open_time' => '09:00', 'close_time' => '18:00'], // Saturday
        ];

        foreach ($defaultHours as $day => $hours) {
            WorkingHour::updateOrCreate(
                ['day_of_week' => $day],
                $hours
            );
        }
    }
}
