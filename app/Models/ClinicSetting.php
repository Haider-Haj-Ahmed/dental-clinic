<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * ClinicSetting
 *
 * Singleton model — exactly one row exists in the table.
 * Never create() directly; use ClinicSetting::instance() to get/create the row,
 * then update() to save changes.
 *
 * Accessor helpers used throughout the app:
 *   ClinicSetting::instance()->timezone
 *   ClinicSetting::instance()->currency_code
 */
class ClinicSetting extends Model
{
    protected $fillable = [
        'clinic_name',
        'clinic_email',
        'clinic_phone',
        'clinic_address',
        'clinic_logo_path',
        'website',
        'timezone',
        'currency_code',
        'currency_symbol',
        'date_format',
        'time_format',
        'language',
        'primary_color',
        'tax_name',
        'tax_rate',
        'invoice_prefix',
        'invoice_starting_number',
        'appointment_slot_minutes',
        'cancellation_policy_hours',
        'reminder_first_hours',
        'reminder_second_hours',
        'reminder_sms_enabled',
        'reminder_email_enabled',
        'reminder_whatsapp_enabled',
        'require_2fa',
        'session_timeout_minutes',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate'                  => 'integer',
            'invoice_starting_number'   => 'integer',
            'appointment_slot_minutes'  => 'integer',
            'cancellation_policy_hours' => 'integer',
            'reminder_first_hours'      => 'integer',
            'reminder_second_hours'     => 'integer',
            'reminder_sms_enabled'      => 'boolean',
            'reminder_email_enabled'    => 'boolean',
            'reminder_whatsapp_enabled' => 'boolean',
            'require_2fa'               => 'boolean',
            'session_timeout_minutes'   => 'integer',
        ];
    }

    /**
     * Get the single clinic settings row, creating it with defaults if missing.
     */
    public static function instance(): static
    {
        return static::firstOrCreate([], []);
    }
}
