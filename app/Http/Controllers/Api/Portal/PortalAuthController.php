<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Mail\PortalOtpMail;
use App\Models\Patient;
use App\Models\PatientOtpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * PortalAuthController
 *
 * Passwordless OTP login for the patient portal.
 *
 * Flow:
 *   1. POST /portal/auth/request-otp  — patient submits phone or email
 *   2. OTP sent via email (SMS in future)
 *   3. POST /portal/auth/verify-otp   — patient submits OTP
 *   4. Sanctum token issued for Patient model
 *   5. POST /portal/auth/logout       — revoke token
 */
class PortalAuthController extends Controller
{
    /**
     * Request OTP
     * POST /api/v1/portal/auth/request-otp
     *
     * Always returns 200 to prevent patient enumeration.
     * Throttled to 3 requests per 10 minutes per IP.
     */
    public function requestOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required_without:phone', 'nullable', 'email'],
            'phone' => ['required_without:email', 'nullable', 'string'],
        ]);

        $patient = null;

        if ($request->filled('email')) {
            $patient = Patient::where('email', $request->input('email'))
                ->where('is_active', true)
                ->first();
        } elseif ($request->filled('phone')) {
            $patient = Patient::where('phone', $request->input('phone'))
                ->where('is_active', true)
                ->first();
        }

        // Always return same response — prevent enumeration
        if (! $patient || ! $patient->email) {
            return response()->json([
                'message' => 'If we found an account matching your details, a code has been sent.',
            ]);
        }

        // Invalidate any previous unused codes
        PatientOtpCode::where('patient_id', $patient->id)
            ->where('used', false)
            ->update(['used' => true]);

        // Generate 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        PatientOtpCode::create([
            'patient_id' => $patient->id,
            'code'       => $otp,
            'channel'    => 'email',
            'expires_at' => now()->addMinutes(10),
        ]);

        Mail::to($patient->email, $patient->first_name)->queue(new PortalOtpMail($patient, $otp));

        return response()->json([
            'message' => 'If we found an account matching your details, a code has been sent.',
        ]);
    }

    /**
     * Verify OTP and issue token
     * POST /api/v1/portal/auth/verify-otp
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'email'       => ['required_without:phone', 'nullable', 'email'],
            'phone'       => ['required_without:email', 'nullable', 'string'],
            'code'        => ['required', 'string', 'digits:6'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $patient = null;

        if ($request->filled('email')) {
            $patient = Patient::where('email', $request->input('email'))->where('is_active', true)->first();
        } elseif ($request->filled('phone')) {
            $patient = Patient::where('phone', $request->input('phone'))->where('is_active', true)->first();
        }

        if (! $patient) {
            return response()->json(['message' => 'Invalid code.'], 422);
        }

        $otpRecord = PatientOtpCode::where('patient_id', $patient->id)
            ->where('code', $request->input('code'))
            ->where('used', false)
            ->where('expires_at', '>', now('UTC'))
            ->latest()
            ->first();

        if (! $otpRecord) {
            return response()->json(['message' => 'Invalid or expired code. Please request a new one.'], 422);
        }

        // Mark OTP as used
        $otpRecord->update(['used' => true]);

        // Issue Sanctum token for this Patient
        $token = $patient->createToken(
            $request->input('device_name'),
            $patient->portalTokenAbilities(),
            now()->addDays(30),
        )->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'patient'    => [
                'id'         => $patient->id,
                'first_name' => $patient->first_name,
                'last_name'  => $patient->last_name,
                'email'      => $patient->email,
                'phone'      => $patient->phone,
            ],
            'expires_in_days' => 30,
        ]);
    }

    /**
     * Logout — revoke current portal token
     * POST /api/v1/portal/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('sanctum')?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
