<?php

namespace Tests\Feature\Api;

use App\Mail\PortalOtpMail;
use App\Models\Patient;
use App\Models\PatientOtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PatientPortalTest extends TestCase
{
    use RefreshDatabase;

    private function createPatient(array $attrs = []): Patient
    {
        return Patient::factory()->create(array_merge([
            'email'     => 'patient@test.com',
            'is_active' => true,
        ], $attrs));
    }

    public function test_request_otp_queues_email_for_known_patient(): void
    {
        Mail::fake();
        $this->createPatient();

        $this->postJson('/api/v1/portal/auth/request-otp', ['email' => 'patient@test.com'])
             ->assertOk();

        Mail::assertQueued(PortalOtpMail::class);
    }

    public function test_request_otp_returns_200_for_unknown_email_to_prevent_enumeration(): void
    {
        Mail::fake();

        $this->postJson('/api/v1/portal/auth/request-otp', ['email' => 'nobody@unknown.com'])
             ->assertOk()
             ->assertJsonPath('message', 'If we found an account matching your details, a code has been sent.');

        Mail::assertNothingQueued();
    }

    public function test_verify_otp_issues_sanctum_token(): void
    {
        Mail::fake();
        $patient = $this->createPatient();

        // Request OTP
        $this->postJson('/api/v1/portal/auth/request-otp', ['email' => 'patient@test.com']);

        $otp = PatientOtpCode::where('patient_id', $patient->id)->latest()->first();

        $response = $this->postJson('/api/v1/portal/auth/verify-otp', [
            'email'       => 'patient@test.com',
            'code'        => $otp->code,
            'device_name' => 'iPhone',
        ])->assertOk()->assertJsonStructure(['token', 'patient']);

        $this->assertNotEmpty($response->json('token'));
    }

    public function test_verify_otp_fails_with_wrong_code(): void
    {
        $this->createPatient();

        // Create a real OTP record
        $patient = Patient::where('email', 'patient@test.com')->first();
        PatientOtpCode::create([
            'patient_id' => $patient->id,
            'code'       => '123456',
            'channel'    => 'email',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->postJson('/api/v1/portal/auth/verify-otp', [
            'email'       => 'patient@test.com',
            'code'        => '000000',
            'device_name' => 'iPhone',
        ])->assertStatus(422);
    }

    public function test_verify_otp_fails_with_expired_code(): void
    {
        $patient = $this->createPatient();
        PatientOtpCode::create([
            'patient_id' => $patient->id,
            'code'       => '123456',
            'channel'    => 'email',
            'expires_at' => now()->subMinute(),
        ]);

        $this->postJson('/api/v1/portal/auth/verify-otp', [
            'email'       => 'patient@test.com',
            'code'        => '123456',
            'device_name' => 'iPhone',
        ])->assertStatus(422);
    }

    public function test_authenticated_patient_can_get_profile(): void
    {
        $patient = $this->createPatient(['first_name' => 'Ahmad', 'last_name' => 'Khalil']);
        $token   = $patient->createToken('portal', ['portal:read'])->plainTextToken;

        $this->withToken($token)
             ->getJson('/api/v1/portal/me')
             ->assertOk()
             ->assertJsonPath('data.first_name', 'Ahmad')
             ->assertJsonPath('data.last_name', 'Khalil');
    }

    public function test_patient_cannot_access_staff_api_with_portal_token(): void
    {
        $patient = $this->createPatient();
        $token   = $patient->createToken('portal', ['portal:read'])->plainTextToken;

        // Portal token should not work on staff endpoints
        $this->withToken($token)
             ->getJson('/api/v1/patients')
             ->assertUnauthorized();
    }

    public function test_portal_logout_revokes_token(): void
    {
        $patient = $this->createPatient();
        $token   = $patient->createToken('portal', ['portal:read'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/portal/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/v1/portal/me')->assertUnauthorized();
    }
}
