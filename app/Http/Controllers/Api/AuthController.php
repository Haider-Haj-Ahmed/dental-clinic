<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
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
        $token = $user->createToken($credentials['device_name'], $abilities)->plainTextToken;

        return $this->successResponse([
            'token'      => $token,
            'token_type' => 'Bearer',
            'abilities'  => $abilities,
            'user'       => UserResource::make($user),
        ], 'Login successful.');
    }

    public function me(Request $request): UserResource
    {
        /** @var User $user */
        $user = $request->user();

        return UserResource::make($user);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        PersonalAccessToken::findToken($request->bearerToken())?->delete();

        return $this->successResponse(message: 'Logged out successfully.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()?->tokens()->delete();

        return $this->successResponse(message: 'All tokens were revoked successfully.');
    }
}
