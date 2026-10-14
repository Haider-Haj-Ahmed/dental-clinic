<?php

namespace Tests\Feature\Api;

use App\Mail\PasswordChangedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_password_succeeds_with_new_password(): void
    {
        Mail::fake();
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/auth/change-password', [
                 'current_password'      => 'password',
                 'password'              => 'NewPassword1!',
                 'password_confirmation' => 'NewPassword1!',
             ])->assertOk();

        $this->assertTrue(Hash::check('NewPassword1!', $user->fresh()->password));
        Mail::assertQueued(PasswordChangedMail::class);
    }

    public function test_change_password_fails_with_wrong_current_password(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
             ->postJson('/api/v1/auth/change-password', [
                 'current_password'      => 'wrong-password',
                 'password'              => 'NewPassword1!',
                 'password_confirmation' => 'NewPassword1!',
             ])->assertStatus(422)
               ->assertJsonPath('errors.current_password.0', 'The current password is incorrect.');
    }

    public function test_change_password_rejects_recently_used_password(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Record current password in history
        $user->recordPasswordHistory();

        $this->withToken($token)
             ->postJson('/api/v1/auth/change-password', [
                 'current_password'      => 'password',
                 'password'              => 'password',   // same as current
                 'password_confirmation' => 'password',
             ])->assertStatus(422)
               ->assertJsonPath('errors.password.0', 'You have used this password recently. Please choose a different password.');
    }

    public function test_change_password_revokes_other_tokens_but_keeps_current(): void
    {
        $user   = User::factory()->create();
        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        $this->withToken($token1)
             ->postJson('/api/v1/auth/change-password', [
                 'current_password'      => 'password',
                 'password'              => 'NewPassword1!',
                 'password_confirmation' => 'NewPassword1!',
             ])->assertOk();

        // token1 should still work
        $this->withToken($token1)->getJson('/api/v1/auth/me')->assertOk();
        // token2 should be revoked
        $this->withToken($token2)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_password_changed_at_is_set_after_change(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->assertNull($user->password_changed_at);

        $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertOk();

        $this->assertNotNull($user->fresh()->password_changed_at);
    }
}
