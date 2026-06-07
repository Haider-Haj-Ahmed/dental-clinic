<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Appointment::class, 'appointment');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $appointments = Appointment::query()
            ->with(['patient', 'provider', 'creator'])
            ->when($user->isProvider(), function ($query) use ($user) {
                $providerId = $user->providerProfile?->id;
                $query->where('provider_id', $providerId ?? 0);
            })
            ->when($request->filled('provider_id'), fn ($q) => $q->where('provider_id', $request->integer('provider_id')))
            ->when($request->filled('patient_id'), fn ($q) => $q->where('patient_id', $request->integer('patient_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->string('status')))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('start_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('start_at', '<=', $request->date('date_to')))
            ->orderBy('start_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return AppointmentResource::collection($appointments);
    }

    public function store(StoreAppointmentRequest $request): AppointmentResource
    {
        $payload = $request->validated();

        /** @var User $user */
        $user = $request->user();
        $payload['created_by'] = $user->id;

        $appointment = DB::transaction(function () use ($payload) {
            $this->assertNoTimeConflict(
                $payload['provider_id'],
                $payload['patient_id'],
                $payload['start_at'],
                $payload['end_at'],
                lock: true,
            );

            if (($payload['status'] ?? null) === Appointment::STATUS_CANCELLED) {
                $payload['cancelled_at'] = now();
            }

            return Appointment::query()->create($payload);
        });

        return AppointmentResource::make($appointment->load(['patient', 'provider', 'creator']));
    }

    public function show(Appointment $appointment): AppointmentResource
    {
        return AppointmentResource::make($appointment->load(['patient', 'provider', 'creator']));
    }

    public function update(UpdateAppointmentRequest $request, Appointment $appointment): AppointmentResource
    {
        $payload = $request->validated();

        $providerId = $payload['provider_id'] ?? $appointment->provider_id;
        $patientId  = $payload['patient_id']  ?? $appointment->patient_id;
        $startAt    = $payload['start_at']    ?? $appointment->start_at;
        $endAt      = $payload['end_at']      ?? $appointment->end_at;

        DB::transaction(function () use ($appointment, $payload, $providerId, $patientId, $startAt, $endAt) {
            $this->assertNoTimeConflict($providerId, $patientId, $startAt, $endAt, $appointment->id, lock: true);

            if (array_key_exists('status', $payload)) {
                $payload['cancelled_at'] = $payload['status'] === Appointment::STATUS_CANCELLED ? now() : null;
            }

            $appointment->update($payload);
        });

        return AppointmentResource::make($appointment->refresh()->load(['patient', 'provider', 'creator']));
    }

    public function destroy(Appointment $appointment): Response
    {
        $appointment->delete();

        return response()->noContent();
    }

    private function assertNoTimeConflict(
        int $providerId,
        int $patientId,
        string|DateTimeInterface $startAt,
        string|DateTimeInterface $endAt,
        ?int $ignoreAppointmentId = null,
        bool $lock = false,
    ): void {
        $startAtValue = $startAt instanceof DateTimeInterface ? Carbon::instance($startAt) : Carbon::parse($startAt);
        $endAtValue   = $endAt   instanceof DateTimeInterface ? Carbon::instance($endAt)   : Carbon::parse($endAt);

        $base = Appointment::query()
            ->when($ignoreAppointmentId !== null, fn ($q) => $q->whereKeyNot($ignoreAppointmentId))
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_NO_SHOW])
            ->where('start_at', '<', $endAtValue)
            ->where('end_at', '>', $startAtValue);

        if ($lock) {
            $base->lockForUpdate();
        }

        if ((clone $base)->where('provider_id', $providerId)->exists()) {
            throw ValidationException::withMessages([
                'provider_id' => ['The provider already has an overlapping appointment.'],
            ]);
        }

        if ((clone $base)->where('patient_id', $patientId)->exists()) {
            throw ValidationException::withMessages([
                'patient_id' => ['The patient already has an overlapping appointment.'],
            ]);
        }
    }
}
