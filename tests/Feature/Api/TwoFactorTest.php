<?php

namespace Tests\Feature\Api;

use App\Mail\TwoFactorEnabledMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsVerified(string $role = User::ROLE_OWNER): array
    {
        $user  = User::factory()->create(['role' => $role, 'email_verified_at' => now()]);
        $token = $user->createToken('test')->plainTextToken;
        return [$user, $token];
    }

    public function test_enable_2fa_returns_secret_and_qr_uri(): void
    {
        [$user, $token] = $this->actingAsVerified();

        $this->withToken($token)
             ->postJson('/api/v1/auth/2fa/enable')
             ->assertOk()
             ->assertJsonStructure(['secret', 'qr_code_uri']);

        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
    }

    public function test_confirm_2fa_with_valid_otp_activates_and_returns_recovery_codes(): void
    {
        Mail::fake();
        [$user, $token] = $this->actingAsVerified();

        // Enable first
        $this->withToken($token)->postJson('/api/v1/auth/2fa/enable')->assertOk();
        $secret = decrypt($user->fresh()->two_factor_secret);

        // Generate a valid OTP
        $otp = (new Google2FA)->getCurrentOtp($secret);

        $response = $this->withToken($token)
             ->postJson('/api/v1/auth/2fa/confirm', ['code' => $otp])
             ->assertOk()
             ->assertJsonStructure(['recovery_codes']);

        $this->assertCount(8, $response->json('recovery_codes'));
        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        Mail::assertQueued(TwoFactorEnabledMail::class);
    }

    public function test_confirm_2fa_with_invalid_otp_fails(): void
    {
        [$user, $token] = $this->actingAsVerified();
        $this->withToken($token)->postJson('/api/v1/auth/2fa/enable');

        $this->withToken($token)
             ->postJson('/api/v1/auth/2fa/confirm', ['code' => '000000'])
             ->assertStatus(422);
    }

    public function test_login_requires_2fa_challenge_when_2fa_is_active(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        // Manually enable 2FA
        $secret = (new Google2FA)->generateSecretKey();
        $user->forceFill([
            'two_factor_secret'       => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $user->generateRecoveryCodes();

        $response = $this->postJson('/api/v1/auth/login', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'test',
        ])->assertOk()->assertJsonPath('requires_2fa', true);

        $this->assertArrayHasKey('two_factor_token', $response->json());
        $this->assertArrayNotHasKey('token', $response->json());
    }

    public function test_2fa_challenge_with_valid_otp_returns_sanctum_token(): void
    {
        $user   = User::factory()->create(['email_verified_at' => now()]);
        $secret = (new Google2FA)->generateSecretKey();
        $user->forceFill([
            'two_factor_secret'       => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $user->generateRecoveryCodes();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'test',
        ]);

        $twoFactorToken = $loginResponse->json('two_factor_token');
        $otp = (new Google2FA)->getCurrentOtp($secret);

        $this->postJson('/api/v1/auth/2fa/challenge', [
            'two_factor_token' => $twoFactorToken,
            'code'             => $otp,
            'device_name'      => 'test',
        ])->assertOk()->assertJsonStructure(['token']);
    }

    public function test_2fa_challenge_with_recovery_code_works_and_invalidates_code(): void
    {
        $user   = User::factory()->create(['email_verified_at' => now()]);
        $secret = (new Google2FA)->generateSecretKey();
        $user->forceFill([
            'two_factor_secret'       => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $plainCodes = $user->generateRecoveryCodes();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'test',
        ]);

        $this->postJson('/api/v1/auth/2fa/challenge', [
            'two_factor_token' => $loginResponse->json('two_factor_token'),
            'recovery_code'    => $plainCodes[0],
            'device_name'      => 'test',
        ])->assertOk()->assertJsonStructure(['token']);

        // Code should now be consumed — only 7 remain
        $this->assertCount(7, $user->fresh()->getRecoveryCodes());
    }

    public function test_disable_2fa_with_valid_otp(): void
    {
        Mail::fake();
        $user   = User::factory()->create(['email_verified_at' => now()]);
        $secret = (new Google2FA)->generateSecretKey();
        $user->forceFill([
            'two_factor_secret'       => encrypt($secret),
            'two_factor_confirmed_at' => now(),
        ])->save();
        $token = $user->createToken('test')->plainTextToken;
        $otp   = (new Google2FA)->getCurrentOtp($secret);

        $this->withToken($token)
             ->postJson('/api/v1/auth/2fa/disable', ['code' => $otp])
             ->assertOk();

        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_confirmed_at);
        Mail::assertQueued(TwoFactorEnabledMail::class);
    }
}
