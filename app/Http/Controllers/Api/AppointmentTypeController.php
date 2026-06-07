<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentTypeRequest;
use App\Http\Requests\UpdateAppointmentTypeRequest;
use App\Http\Resources\AppointmentTypeResource;
use App\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AppointmentTypeController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(AppointmentType::class, 'appointment_type');
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $types = AppointmentType::query()
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return AppointmentTypeResource::collection($types);
    }

    public function store(StoreAppointmentTypeRequest $request): AppointmentTypeResource
    {
        $type = AppointmentType::query()->create($request->validated());

        return AppointmentTypeResource::make($type);
    }

    public function show(AppointmentType $appointmentType): AppointmentTypeResource
    {
        return AppointmentTypeResource::make($appointmentType);
    }

    public function update(UpdateAppointmentTypeRequest $request, AppointmentType $appointmentType): AppointmentTypeResource
    {
        $appointmentType->update($request->validated());

        return AppointmentTypeResource::make($appointmentType->refresh());
    }

    public function destroy(AppointmentType $appointmentType): Response
    {
        $appointmentType->delete();

        return response()->noContent();
    }
}
