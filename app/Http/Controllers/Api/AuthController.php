<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /* ══════════════════════════════════════════════════════════
     * REGISTER
     * POST /api/v1/auth/register
     *
     * Only an owner can create accounts for other staff.
     * The first account (no users in DB) is always granted owner
     * role regardless of what role was passed — bootstrapping.
     * ══════════════════════════════════════════════════════════ */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Bootstrap: if no users exist yet, first registration is always owner
        $isFirstUser = User::query()->doesntExist();

        // After bootstrap: only owners may register new staff
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
            'password' => $data['password'],   // hashed by model cast
            'role'     => $data['role'],
        ]);

        $abilities = $user->tokenAbilities();
        $token     = $user->createToken($data['device_name'], $abilities)->plainTextToken;

        return response()->json([
            'message'    => 'Account created successfully.',
            'token'      => $token,
            'token_type' => 'Bearer',
            'abilities'  => $abilities,
            'user'       => UserResource::make($user),
        ], 201);
    }

    /* ══════════════════════════════════════════════════════════
     * LOGIN
     * POST /api/v1/auth/login
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

        $abilities = $user->tokenAbilities();
        $token     = $user->createToken($credentials['device_name'], $abilities)->plainTextToken;

        return response()->json([
            'token'      => $token,
            'token_type' => 'Bearer',
            'abilities'  => $abilities,
            'user'       => UserResource::make($user),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * ME
     * GET /api/v1/auth/me
     * ══════════════════════════════════════════════════════════ */
    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    /* ══════════════════════════════════════════════════════════
     * LOGOUT (current token only)
     * POST /api/v1/auth/logout
     * ══════════════════════════════════════════════════════════ */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        PersonalAccessToken::findToken($request->bearerToken())?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /* ══════════════════════════════════════════════════════════
     * LOGOUT ALL (revoke every token for this user)
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
     *
     * Sends a password reset link to the given email address.
     * Uses Laravel's built-in Password broker which hashes the
     * token and stores it in password_reset_tokens (already
     * created by the users migration).
     *
     * Always returns 200 regardless of whether the email exists
     * to avoid user enumeration.
     * ══════════════════════════════════════════════════════════ */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        // Both RESET_LINK_SENT and INVALID_USER return 200
        // so callers cannot enumerate registered emails
        return response()->json([
            'message' => __($status),
        ]);
    }

    /* ══════════════════════════════════════════════════════════
     * RESET PASSWORD
     * POST /api/v1/auth/reset-password
     *
     * Validates the token, resets the password, fires the
     * PasswordReset event (invalidates all existing tokens via
     * the default listener), and issues a fresh Sanctum token.
     * ══════════════════════════════════════════════════════════ */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                // Revoke all existing Sanctum tokens on password reset
                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => 'Password has been reset. Please log in with your new password.',
        ]);
    }
}
