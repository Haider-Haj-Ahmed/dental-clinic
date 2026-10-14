<?php

namespace Tests\Feature\Api;

use App\Events\AppointmentBooked;
use App\Events\LowStockAlert;
use App\Events\RecallOverdue;
use App\Jobs\CheckLowStockJob;
use App\Jobs\SendAppointmentRemindersJob;
use App\Jobs\SendRecallRemindersJob;
use App\Mail\AppointmentCancelledMail;
use App\Mail\AppointmentConfirmationMail;
use App\Mail\AppointmentReminderMail;
use App\Mail\InvoiceFinalizedMail;
use App\Mail\LowStockAlertMail;
use App\Mail\NewDeviceLoginMail;
use App\Mail\PasswordChangedMail;
use App\Mail\PaymentReceiptMail;
use App\Mail\RecallReminderMail;
use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Operatory;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\Recall;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailDispatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_queues_new_device_login_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->postJson('/api/v1/auth/login', [
            'email'       => $user->email,
            'password'    => 'password',
            'device_name' => 'test-device',
        ])->assertOk();

        Mail::assertQueued(NewDeviceLoginMail::class);
    }

    public function test_change_password_queues_password_changed_mail(): void
    {
        Mail::fake();
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/auth/change-password', [
            'current_password'      => 'password',
            'password'              => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])->assertOk();

        Mail::assertQueued(PasswordChangedMail::class);
    }

    public function test_appointment_booked_event_does_not_queue_mail_without_patient_email(): void
    {
        Mail::fake();

        $patient = Patient::factory()->create(['email' => null]);
        $provider = Provider::factory()->create();
        $type = AppointmentType::factory()->create();
        $op = Operatory::factory()->create();
        $owner = User::factory()->owner()->create();

        $appt = Appointment::factory()->create([
            'patient_id'          => $patient->id,
            'provider_id'         => $provider->id,
            'appointment_type_id' => $type->id,
            'operatory_id'        => $op->id,
            'created_by'          => $owner->id,
        ]);

        $appt->load(['patient', 'provider', 'appointmentType']);
        event(new AppointmentBooked($appt));

        Mail::assertNothingQueued();
    }

    public function test_recall_reminder_job_queues_mail_for_patient_with_email(): void
    {
        Mail::fake();

        $patient = Patient::factory()->create(['email' => 'patient@test.com']);
        Recall::factory()->create([
            'patient_id' => $patient->id,
            'due_date'   => now()->subDays(5)->toDateString(),
            'status'     => Recall::STATUS_PENDING,
        ]);

        (new SendRecallRemindersJob)->handle();

        Mail::assertQueued(RecallReminderMail::class);
    }

    public function test_low_stock_job_queues_mail_to_owner(): void
    {
        Mail::fake();

        User::factory()->owner()->create(['email' => 'owner@test.com']);
        InventoryItem::factory()->create([
            'current_stock' => 1,
            'reorder_level' => 5,
            'is_active'     => true,
        ]);

        (new CheckLowStockJob)->handle();

        Mail::assertQueued(LowStockAlertMail::class, fn ($mail) =>
            $mail->hasTo('owner@test.com')
        );
    }
}
