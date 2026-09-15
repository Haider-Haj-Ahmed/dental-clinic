<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\TwoFactorChallengeRequest;
use App\Http\Resources\UserResource;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function __construct(private readonly Google2FA $google2fa) {}

    /* ══════════════════════════════════════════════════════════
     * REGISTER
     * POST /api/v1/auth/register
     * ══════════════════════════════════════════════════════════ */
    public function register(RegisterRequest $request): JsonResponse
    {
        $isFirstUser = User::query()->doesntExist();

        if (! $isFirstUser) {
            $requestingUser = $request->user();
            if (! $requestingUser || ! $requestingUser->isOwner()) {
                return response()->json([
                    'message' => 'Only clinic owners can register new staff accounts.',
                ], 403);
            }
        }

        $data = $request->validated();
        if ($isFirstUser) {
            $data['role'] = User::ROLE_OWNER;
        }

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => $data['role'],
        ]);

        // Fire Registered event → queues VerifyEmailMail
        event(new Registered($user));

        $abilities = $user->tokenAbilities();
        $token     = $user->createToken($data['device_name'], $abilities)->plainTextToken;

        return response()->json([
            'message'        => 'Account created successfully. A verification email has been sent.',
            'token'          => $token,
            'token_type'     => 'Bearer',
            'abilities'      => $abilities,
            'email_verified' => false,
            'two_factor'     => false,
            'user'           => UserResource::make($user),
        ], 201);
    }

    /* ══════════════════════════════════════════════════════════
     * LOGIN
     * POST /api/v1/auth/login
     *
     * 2FA integration:
     *   If the user has 2FA enabled and their email is verified,
     *   we do NOT issue a full Sanctum token. Instead we cache
     *   the user ID under a short-lived random token and return
     *   requires_2fa: true. The client must then call
     *   POST /auth/2fa/challenge with the temp token + OTP.
     * ══════════════════════════════════════════════════════════ */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // 2FA gate — only when email is verified and 2FA is confirmed active
        if ($user->hasVerifiedEmail() && $user->hasTwoFactorEnabled()) {
            // Store user ID + device_name in cache for 10 minutes
            $twoFactorToken = Str::random(64);

            Cache::put(
                "2fa_pending:{$twoFactorToken}",
                ['user_id' => $user->id, 'device_name' => $credentials['device_name']],
                now()->addMinutes(10),
            );

            return response()->json([
                'requires_2fa'     => true,
                'two_factor_token' => $twoFactorToken,
                'message'          => 'OTP required. Submit your authenticator code to /auth/2fa/challenge.',
            ], 200);
        }

        $abilities = $user->tokenAbilities();
        $token     = $user->createToken($credentials['device_name'], $abilities)->plainTextToken;

        return response()->json([
            'token'          => $token,
            'token_type'     => 'Bearer',
            'abilities'      => $abilities,
            'email_verified' => $user->hasVerifiedEmail(),
            'two_factor'     => $user->hasTwoFactorEnabled(),
            'user'           => UserResource::make($user),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * TWO FACTOR CHALLENGE
     * POST /api/v1/auth/2fa/challenge
     *
     * Called after login when requires_2fa: true.
     * Accepts either:
     *   - code          — 6-digit TOTP from authenticator app
     *   - recovery_code — 8-character one-time recovery code
     *
     * On success: issues full Sanctum token and deletes the
     * temporary 2fa_pending cache entry.
     * ══════════════════════════════════════════════════════════ */
    public function twoFactorChallenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Retrieve pending session from cache
        $pending = Cache::get("2fa_pending:{$data['two_factor_token']}");

        if (! $pending) {
            return response()->json([
                'message' => 'The 2FA session has expired or is invalid. Please log in again.',
            ], 422);
        }

        /** @var User|null $user */
        $user = User::find($pending['user_id']);

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            Cache::forget("2fa_pending:{$data['two_factor_token']}");
            return response()->json(['message' => 'Invalid session.'], 422);
        }

        $authenticated = false;

        // Validate TOTP code
        if (! empty($data['code'])) {
            $secret        = decrypt($user->two_factor_secret);
            $authenticated = $this->google2fa->verifyKey($secret, $data['code']);
        }

        // Validate recovery code (if OTP not provided or failed)
        if (! $authenticated && ! empty($data['recovery_code'])) {
            $authenticated = $user->useRecoveryCode($data['recovery_code']);
        }

        if (! $authenticated) {
            return response()->json([
                'message' => 'The provided code is invalid.',
            ], 422);
        }

        // Clear pending cache entry
        Cache::forget("2fa_pending:{$data['two_factor_token']}");

        $abilities = $user->tokenAbilities();
        $token     = $user->createToken($pending['device_name'], $abilities)->plainTextToken;

        return response()->json([
            'token'          => $token,
            'token_type'     => 'Bearer',
            'abilities'      => $abilities,
            'email_verified' => $user->hasVerifiedEmail(),
            'two_factor'     => true,
            'user'           => UserResource::make($user),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * ME
     * GET /api/v1/auth/me
     * ══════════════════════════════════════════════════════════ */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user'           => UserResource::make($user),
            'email_verified' => $user->hasVerifiedEmail(),
            'two_factor'     => $user->hasTwoFactorEnabled(),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * VERIFY EMAIL
     * GET /api/v1/auth/email/verify/{id}/{hash}
     * ══════════════════════════════════════════════════════════ */
    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json([
                'message' => 'The verification link is invalid or has expired.',
            ], 422);
        }

        /** @var User|null $user */
        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json([
                'message' => 'The verification link is invalid.',
            ], 422);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email address is already verified.',
                'user'    => UserResource::make($user),
            ]);
        }

        $user->markEmailAsVerified();

        return response()->json([
            'message'    => 'Email address verified successfully.',
            'two_factor' => $user->hasTwoFactorEnabled(),
            'user'       => UserResource::make($user),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * RESEND VERIFICATION EMAIL
     * POST /api/v1/auth/email/resend
     * ══════════════════════════════════════════════════════════ */
    public function resendVerification(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email address is already verified.',
            ]);
        }

        Mail::to($user->email, $user->name)->queue(new VerifyEmailMail($user));

        return response()->json([
            'message' => 'Verification email has been resent.',
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * LOGOUT
     * POST /api/v1/auth/logout
     * ══════════════════════════════════════════════════════════ */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        PersonalAccessToken::findToken($request->bearerToken())?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /* ══════════════════════════════════════════════════════════
     * LOGOUT ALL
     * POST /api/v1/auth/logout-all
     * ══════════════════════════════════════════════════════════ */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return response()->json(['message' => 'All tokens were revoked successfully.']);
    }

    /* ══════════════════════════════════════════════════════════
     * FORGOT PASSWORD
     * POST /api/v1/auth/forgot-password
     * ══════════════════════════════════════════════════════════ */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        return response()->json(['message' => __($status)]);
    }

    /* ══════════════════════════════════════════════════════════
     * RESET PASSWORD
     * POST /api/v1/auth/reset-password
     * ══════════════════════════════════════════════════════════ */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        // Pre-validate password history before handing to Password::reset
        // We need the user to check history, so look them up first
        $user = User::where('email', $request->input('email'))->first();

        if ($user && $user->passwordUsedBefore($request->input('password'))) {
            throw ValidationException::withMessages([
                'password' => ['You have used this password recently. Please choose a different password.'],
            ]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Record in history after saving
                $user->recordPasswordHistory();

                // Revoke all tokens on password reset — forces re-login
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json([
            'message' => 'Password has been reset. Please log in with your new password.',
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * CHANGE PASSWORD (authenticated)
     * POST /api/v1/auth/change-password
     *
     * Allows an authenticated user to change their own password
     * without going through the forgot-password flow.
     * Requires current password confirmation.
     * Enforces password history (last 5 passwords rejected).
     * Revokes all OTHER tokens — current session stays active.
     * ══════════════════════════════════════════════════════════ */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(8)->mixedCase()->numbers()],
        ]);

        /** @var User $user */
        $user = $request->user();

        // Verify current password
        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Reject if new password matches any of last 5
        if ($user->passwordUsedBefore($request->input('password'))) {
            throw ValidationException::withMessages([
                'password' => ['You have used this password recently. Please choose a different password.'],
            ]);
        }

        $user->forceFill([
            'password'       => Hash::make($request->input('password')),
            'remember_token' => Str::random(60),
        ])->save();

        // Record in history
        $user->recordPasswordHistory();

        // Revoke all OTHER tokens — current stays valid so the user is not logged out
        $currentTokenId = $user->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json([
            'message' => 'Password changed successfully. All other sessions have been revoked.',
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * LIST TOKENS (active sessions)
     * GET /api/v1/auth/tokens
     *
     * Returns all active Sanctum tokens for the authenticated
     * user. Excludes the secret token value — only metadata.
     * Useful for showing the user their active devices and
     * allowing them to revoke specific ones.
     * ══════════════════════════════════════════════════════════ */
    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()
            ->tokens()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(fn ($token) => [
                'id'           => $token->id,
                'name'         => $token->name,
                'abilities'    => $token->abilities,
                'last_used_at' => $token->last_used_at?->toIso8601String(),
                'created_at'   => $token->created_at->toIso8601String(),
                'expires_at'   => $token->expires_at?->toIso8601String(),
                'is_current'   => $token->id === $request->user()->currentAccessToken()->id,
            ]);

        return response()->json([
            'data'  => $tokens,
            'total' => $tokens->count(),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * REVOKE SPECIFIC TOKEN
     * DELETE /api/v1/auth/tokens/{tokenId}
     *
     * Revokes any single token belonging to the authenticated
     * user by its ID. A user can revoke any of their own tokens
     * including the current one (equivalent to logout).
     * They cannot revoke another user's tokens.
     * ══════════════════════════════════════════════════════════ */
    public function revokeToken(Request $request, int $tokenId): JsonResponse
    {
        $deleted = $request->user()
            ->tokens()
            ->where('id', $tokenId)
            ->delete();

        if (! $deleted) {
            return response()->json([
                'message' => 'Token not found or does not belong to your account.',
            ], 404);
        }

        return response()->json([
            'message' => 'Token revoked successfully.',
        ]);
    }
}
