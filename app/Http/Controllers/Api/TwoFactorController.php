<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmTwoFactorRequest;
use App\Mail\TwoFactorEnabledMail;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;

/**
 * TwoFactorController
 *
 * Manages the full 2FA lifecycle for a user:
 *   1. enable()              — generates secret + QR code URI
 *   2. confirm()             — validates first OTP, activates 2FA, returns recovery codes
 *   3. disable()             — deactivates 2FA (requires OTP confirmation)
 *   4. recoveryCodes()       — list remaining recovery codes
 *   5. regenerateRecoveryCodes() — invalidate all codes and generate fresh set
 *
 * Integration with login:
 *   When 2FA is active, AuthController@login returns requires_2fa: true
 *   with a short-lived two_factor_token instead of a full Sanctum token.
 *   The client submits that token + OTP to AuthController@twoFactorChallenge.
 *
 * All routes require: auth:sanctum + verified email.
 */
class TwoFactorController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /* ══════════════════════════════════════════════════════════
     * ENABLE
     * POST /api/v1/auth/2fa/enable
     *
     * Generates a TOTP secret and returns a QR code URI.
     * 2FA is NOT active yet — user must call confirm() with
     * a valid OTP to activate it.
     *
     * Idempotent: calling again regenerates the secret if 2FA
     * is not yet confirmed (allows re-scanning the QR code).
     * ══════════════════════════════════════════════════════════ */
    public function enable(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Already confirmed — cannot re-enable without disabling first
        if ($user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Two-factor authentication is already enabled. Disable it first to re-configure.',
            ], 422);
        }

        // Generate and store encrypted secret
        $secret = $this->google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret'       => encrypt($secret),
            'two_factor_confirmed_at' => null,
        ])->save();

        // QR code URI (client renders this as a QR code image)
        $qrCodeUri = $this->google2fa->getQRCodeUrl(
            config('app.clinic_name', config('app.name')),
            $user->email,
            $secret,
        );

        return response()->json([
            'message'    => 'Scan the QR code with your authenticator app, then call /2fa/confirm with a valid OTP.',
            'secret'     => $secret,      // Show to user as fallback for manual entry
            'qr_code_uri'=> $qrCodeUri,   // Client renders this as a QR code image
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * CONFIRM
     * POST /api/v1/auth/2fa/confirm
     *
     * Validates the first OTP from the authenticator app.
     * On success: marks 2FA as confirmed, generates recovery
     * codes, sends security alert email.
     *
     * Recovery codes are returned ONCE here — they cannot be
     * retrieved again, only regenerated.
     * ══════════════════════════════════════════════════════════ */
    public function confirm(ConfirmTwoFactorRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasPendingTwoFactor()) {
            return response()->json([
                'message' => '2FA setup has not been initiated. Call /2fa/enable first.',
            ], 422);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid  = $this->google2fa->verifyKey($secret, $request->validated('code'));

        if (! $valid) {
            return response()->json([
                'message' => 'The provided OTP code is invalid. Please try again.',
            ], 422);
        }

        // Mark as confirmed
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        // Generate recovery codes — returned plaintext ONCE
        $recoveryCodes = $user->generateRecoveryCodes();

        // Send security alert email (queued)
        Mail::to($user->email, $user->name)->queue(new TwoFactorEnabledMail($user, true));

        return response()->json([
            'message'        => 'Two-factor authentication has been enabled successfully.',
            'recovery_codes' => $recoveryCodes,
            'warning'        => 'Save these recovery codes in a safe place. They will not be shown again.',
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * DISABLE
     * POST /api/v1/auth/2fa/disable
     *
     * Requires a valid OTP to confirm the user's intent.
     * Clears all 2FA data and sends security alert email.
     * ══════════════════════════════════════════════════════════ */
    public function disable(ConfirmTwoFactorRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Two-factor authentication is not currently enabled.',
            ], 422);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid  = $this->google2fa->verifyKey($secret, $request->validated('code'));

        if (! $valid) {
            return response()->json([
                'message' => 'The provided OTP code is invalid.',
            ], 422);
        }

        $user->disableTwoFactor();

        // Security alert — disabled is more sensitive than enabled
        Mail::to($user->email, $user->name)->queue(new TwoFactorEnabledMail($user, false));

        return response()->json([
            'message' => 'Two-factor authentication has been disabled.',
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * RECOVERY CODES
     * GET /api/v1/auth/2fa/recovery-codes
     *
     * Returns the count of remaining recovery codes.
     * Does NOT return the plain-text codes (only shown at setup).
     * ══════════════════════════════════════════════════════════ */
    public function recoveryCodes(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], 422);
        }

        return response()->json([
            'remaining_codes' => $user->getRecoveryCodes()->count(),
            'warning'         => $remaining = $user->getRecoveryCodes()->count() <= 2
                ? 'You have ' . $remaining . ' recovery codes left. Regenerate them soon.'
                : null,
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * REGENERATE RECOVERY CODES
     * POST /api/v1/auth/2fa/recovery-codes
     *
     * Invalidates all existing codes and generates 8 new ones.
     * Requires a valid OTP to confirm intent.
     * ══════════════════════════════════════════════════════════ */
    public function regenerateRecoveryCodes(ConfirmTwoFactorRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], 422);
        }

        $secret = decrypt($user->two_factor_secret);
        $valid  = $this->google2fa->verifyKey($secret, $request->validated('code'));

        if (! $valid) {
            return response()->json([
                'message' => 'The provided OTP code is invalid.',
            ], 422);
        }

        $recoveryCodes = $user->generateRecoveryCodes();

        return response()->json([
            'message'        => 'Recovery codes have been regenerated. Previous codes are now invalid.',
            'recovery_codes' => $recoveryCodes,
            'warning'        => 'Save these codes in a safe place. They will not be shown again.',
        ]);
    }
}
