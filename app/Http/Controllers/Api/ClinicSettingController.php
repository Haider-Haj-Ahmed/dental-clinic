<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClinicSettingRequest;
use App\Models\ClinicSetting;
use Illuminate\Http\JsonResponse;

/**
 * ClinicSettingController
 *
 * Owner-only. Manages the single clinic settings row.
 *
 * Routes:
 *   GET   /api/v1/settings  → show
 *   PATCH /api/v1/settings  → update
 */
class ClinicSettingController extends Controller
{
    /**
     * Return current clinic settings.
     * Owner only — settings contain internal configuration.
     */
    public function show(): JsonResponse
    {
        $settings = ClinicSetting::instance();

        return response()->json([
            'data' => $this->format($settings),
        ]);
    }

    /**
     * Update clinic settings.
     * Only fields sent in the request are updated (PATCH semantics).
     */
    public function update(UpdateClinicSettingRequest $request): JsonResponse
    {
        $settings = ClinicSetting::instance();
        $settings->update($request->validated());

        return response()->json([
            'message' => 'Clinic settings updated successfully.',
            'data'    => $this->format($settings->refresh()),
        ]);
    }

    /**
     * Consistent response shape for both show and update.
     */
    private function format(ClinicSetting $settings): array
    {
        return [
            'identity' => [
                'clinic_name'    => $settings->clinic_name,
                'clinic_email'   => $settings->clinic_email,
                'clinic_phone'   => $settings->clinic_phone,
                'clinic_address' => $settings->clinic_address,
                'clinic_logo_path'=> $settings->clinic_logo_path,
                'website'        => $settings->website,
            ],
            'locale' => [
                'timezone'        => $settings->timezone,
                'currency_code'   => $settings->currency_code,
                'currency_symbol' => $settings->currency_symbol,
                'date_format'     => $settings->date_format,
                'time_format'     => $settings->time_format,
                'language'        => $settings->language,
            ],
            'branding' => [
                'primary_color' => $settings->primary_color,
            ],
            'billing' => [
                'tax_name'                => $settings->tax_name,
                'tax_rate'                => $settings->tax_rate,
                'invoice_prefix'          => $settings->invoice_prefix,
                'invoice_starting_number' => $settings->invoice_starting_number,
            ],
            'appointments' => [
                'appointment_slot_minutes'  => $settings->appointment_slot_minutes,
                'cancellation_policy_hours' => $settings->cancellation_policy_hours,
            ],
            'reminders' => [
                'first_hours'       => $settings->reminder_first_hours,
                'second_hours'      => $settings->reminder_second_hours,
                'sms_enabled'       => $settings->reminder_sms_enabled,
                'email_enabled'     => $settings->reminder_email_enabled,
                'whatsapp_enabled'  => $settings->reminder_whatsapp_enabled,
            ],
            'security' => [
                'require_2fa'             => $settings->require_2fa,
                'session_timeout_minutes' => $settings->session_timeout_minutes,
            ],
            'updated_at' => $settings->updated_at?->toIso8601String(),
        ];
    }
}
