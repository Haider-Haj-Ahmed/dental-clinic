<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/portal/me
 * Returns the authenticated patient's profile.
 */
class PortalMeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $patient = $request->user();

        return response()->json([
            'data' => [
                'id'                     => $patient->id,
                'first_name'             => $patient->first_name,
                'last_name'              => $patient->last_name,
                'gender'                 => $patient->gender,
                'date_of_birth'          => $patient->date_of_birth?->toDateString(),
                'phone'                  => $patient->phone,
                'email'                  => $patient->email,
                'address'                => $patient->address,
                'emergency_contact_name' => $patient->emergency_contact_name,
                'emergency_contact_phone'=> $patient->emergency_contact_phone,
                'blood_type'             => $patient->blood_type,
                'allergies'              => $patient->allergies()->pluck('allergen'),
                'conditions'             => $patient->conditions()->pluck('condition'),
            ],
        ]);
    }
}
