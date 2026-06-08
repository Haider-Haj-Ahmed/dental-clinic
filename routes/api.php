<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppointmentTypeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OperatoryController;
use App\Http\Controllers\Api\PatientAllergyController;
use App\Http\Controllers\Api\PatientConditionController;
use App\Http\Controllers\Api\PatientConsentController;
use App\Http\Controllers\Api\PatientContactController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientMedicationController;
use App\Http\Controllers\Api\ProcedureCodeController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ScheduleBlockController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Auth (public) ────────────────────────────────────────────────────────
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // ─── Authenticated ────────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth session
        Route::prefix('auth')->group(function () {
            Route::get('me',          [AuthController::class, 'me']);
            Route::post('logout',     [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
        });

        // Staff & config (owner only — enforced via policies)
        Route::apiResource('users',             UserController::class);
        Route::apiResource('operatories',       OperatoryController::class);
        Route::apiResource('appointment-types', AppointmentTypeController::class);
        Route::apiResource('procedure-codes',   ProcedureCodeController::class);

        // Providers
        Route::apiResource('providers', ProviderController::class);

        // Patients
        Route::middleware('token.ability:patients:read')->group(function () {
            Route::post('patients/{patient}/archive', [PatientController::class, 'archive'])->withTrashed();
            Route::post('patients/{patient}/restore', [PatientController::class, 'restore'])->withTrashed();
            Route::apiResource('patients', PatientController::class);

            // Patient sub-resources
            Route::apiResource('patients/{patient}/contacts',    PatientContactController::class)->shallow();
            Route::apiResource('patients/{patient}/allergies',   PatientAllergyController::class)->shallow();
            Route::apiResource('patients/{patient}/conditions',  PatientConditionController::class)->shallow();
            Route::apiResource('patients/{patient}/medications', PatientMedicationController::class)->shallow();
            Route::apiResource('patients/{patient}/consents',    PatientConsentController::class)->shallow();
        });

        // Appointments
        Route::middleware('token.ability:appointments:read')->group(function () {
            Route::apiResource('appointments', AppointmentController::class);
        });

        // Schedule blocks
        Route::apiResource('schedule-blocks', ScheduleBlockController::class);
    });
});
