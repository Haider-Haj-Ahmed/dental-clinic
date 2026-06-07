<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppointmentTypeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OperatoryController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ProcedureCodeController;
use App\Http\Controllers\Api\ProviderController;
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

        // Patients — DELETE = soft archive, explicit archive/restore actions
        Route::post('patients/{patient}/archive', [PatientController::class, 'archive'])
            ->withTrashed();
        Route::post('patients/{patient}/restore', [PatientController::class, 'restore'])
            ->withTrashed();
        Route::apiResource('patients', PatientController::class);

        // Appointments
        Route::apiResource('appointments', AppointmentController::class);
    });
});
