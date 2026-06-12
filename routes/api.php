<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppointmentTypeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommunicationLogController;
use App\Http\Controllers\Api\InventoryItemController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoiceItemController;
use App\Http\Controllers\Api\OperatoryController;
use App\Http\Controllers\Api\PatientAllergyController;
use App\Http\Controllers\Api\PatientConditionController;
use App\Http\Controllers\Api\PatientConsentController;
use App\Http\Controllers\Api\PatientContactController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\PatientMedicalCaseController;
use App\Http\Controllers\Api\PatientMedicalDocumentController;
use App\Http\Controllers\Api\PatientMedicationController;
use App\Http\Controllers\Api\PatientTimelineController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PaymentMethodController;
use App\Http\Controllers\Api\PaymentPlanController;
use App\Http\Controllers\Api\ProcedureCodeController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\PurchaseOrderController;
use App\Http\Controllers\Api\RecallController;
use App\Http\Controllers\Api\ScheduleBlockController;
use App\Http\Controllers\Api\SupplierController;
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
        Route::apiResource('payment-methods',   PaymentMethodController::class);

        // Providers
        Route::apiResource('providers', ProviderController::class);

        // Patients + all nested sub-resources
        Route::middleware('token.ability:patients:read')->group(function () {
            Route::post('patients/{patient}/archive',  [PatientController::class, 'archive'])->withTrashed();
            Route::post('patients/{patient}/restore',  [PatientController::class, 'restore'])->withTrashed();
            Route::get('patients/{patient}/timeline',  PatientTimelineController::class)->name('patients.timeline');
            Route::get('patients/{patient}/ledger',    [InvoiceController::class, 'ledger'])->name('patients.ledger');
            Route::apiResource('patients', PatientController::class);

            // Phase 2A — structured sub-tables
            Route::apiResource('patients/{patient}/contacts',    PatientContactController::class)->shallow();
            Route::apiResource('patients/{patient}/allergies',   PatientAllergyController::class)->shallow();
            Route::apiResource('patients/{patient}/conditions',  PatientConditionController::class)->shallow();
            Route::apiResource('patients/{patient}/medications', PatientMedicationController::class)->shallow();
            Route::apiResource('patients/{patient}/consents',    PatientConsentController::class)->shallow();

            // Phase 2B-1 — medical cases
            Route::apiResource('patients/{patient}/medical-cases', PatientMedicalCaseController::class)
                ->parameter('medical-cases', 'medicalCase');

            // Phase 2B-2 — medical documents
            Route::get('patients/{patient}/documents/{document}/download',
                [PatientMedicalDocumentController::class, 'download']
            )->name('patients.documents.download');
            Route::apiResource('patients/{patient}/documents', PatientMedicalDocumentController::class)
                ->parameter('documents', 'document');
        });

        // Appointments
        Route::middleware('token.ability:appointments:read')->group(function () {
            Route::apiResource('appointments', AppointmentController::class);
        });

        // Schedule blocks
        Route::apiResource('schedule-blocks', ScheduleBlockController::class);

        // Phase 3A — recalls & communication logs
        Route::get('recalls/due',                     [RecallController::class, 'due']);
        Route::patch('recalls/{recall}/status',       [RecallController::class, 'updateStatus']);
        Route::post('recalls/{recall}/send-reminder', [RecallController::class, 'sendReminder']);
        Route::apiResource('recalls', RecallController::class);
        Route::get('communication-logs',              [CommunicationLogController::class, 'index']);
        Route::get('communication-logs/{communicationLog}', [CommunicationLogController::class, 'show']);

        // Phase 3B — billing
        Route::post('invoices/{invoice}/finalize',    [InvoiceController::class, 'finalize']);
        Route::post('invoices/{invoice}/void',        [InvoiceController::class, 'void']);
        Route::apiResource('invoices', InvoiceController::class);
        Route::apiResource('invoices/{invoice}/items', InvoiceItemController::class)
            ->parameter('items', 'item')->shallow();
        Route::apiResource('payments',      PaymentController::class)->except(['update']);
        Route::apiResource('payment-plans', PaymentPlanController::class)->except(['update']);

        // Phase 3C — inventory
        Route::get('inventory-items/low-stock',                      [InventoryItemController::class, 'lowStock']);
        Route::post('inventory-items/{inventoryItem}/adjust-stock',  [InventoryItemController::class, 'adjustStock']);
        Route::get('inventory-items/{inventoryItem}/movements',      [InventoryItemController::class, 'movements']);
        Route::apiResource('inventory-items', InventoryItemController::class);
        Route::apiResource('suppliers', SupplierController::class);
        Route::post('purchase-orders/{purchaseOrder}/receive',       [PurchaseOrderController::class, 'receive']);
        Route::post('purchase-orders/{purchaseOrder}/cancel',        [PurchaseOrderController::class, 'cancel']);
        Route::apiResource('purchase-orders', PurchaseOrderController::class)->except(['update']);
    });
});
