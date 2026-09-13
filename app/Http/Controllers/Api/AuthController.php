<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $isFirstUser = User::query()->doesntExist();

        if (! $isFirstUser) {
            $requestingUser = $request->user();
            if (! $requestingUser || ! $requestingUser->isOwner()) {
                return response()->json(['message' => 'Only clinic owners can register new staff accounts.'], 403);
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

        // Fires → AppServiceProvider listener → queues VerifyEmailMail
        event(new Registered($user));

        $abilities = $user->tokenAbilities();
        $token     = $user->createToken($data['device_name'], $abilities)->plainTextToken;

        return response()->json([
            'message'        => 'Account created successfully. A verification email has been sent.',
            'token'          => $token,
            'token_type'     => 'Bearer',
            'abilities'      => $abilities,
            'email_verified' => false,
            'user'           => UserResource::make($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user        = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }

        $abilities = $user->tokenAbilities();
        $token     = $user->createToken($credentials['device_name'], $abilities)->plainTextToken;

        return response()->json([
            'token'          => $token,
            'token_type'     => 'Bearer',
            'abilities'      => $abilities,
            'email_verified' => $user->hasVerifiedEmail(),
            'user'           => UserResource::make($user),
        ]);
    }

    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }

    public function verifyEmail(Request $request, string $id, string $hash): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            return response()->json(['message' => 'The verification link is invalid or has expired.'], 422);
        }

        $user = User::find($id);
        if (! $user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'The verification link is invalid.'], 422);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email address is already verified.', 'user' => UserResource::make($user)]);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email address verified successfully.', 'user' => UserResource::make($user)]);
    }

    public function resendVerification(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email address is already verified.']);
        }

        Mail::to($user->email, $user->name)->queue(new VerifyEmailMail($user));

        return response()->json(['message' => 'Verification email has been resent.']);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        PersonalAccessToken::findToken($request->bearerToken())?->delete();
        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();
        return response()->json(['message' => 'All tokens were revoked successfully.']);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));
        return response()->json(['message' => __($status)]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => [__($status)]]);
        }

        return response()->json(['message' => 'Password has been reset. Please log in with your new password.']);
    }
}
