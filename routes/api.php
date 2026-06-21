<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppointmentTypeController;
use App\Http\Controllers\Api\AiAnalysisController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommunicationLogController;
use App\Http\Controllers\Api\DashboardController;
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
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ScheduleBlockController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ─── Auth (public) — uses 'auth' named rate limiter ──────────────────────
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth');

    // ─── Authenticated — uses 'api' named rate limiter ────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        // Auth session — no ability restriction
        Route::prefix('auth')->group(function () {
            Route::get('me',          [AuthController::class, 'me']);
            Route::post('logout',     [AuthController::class, 'logout']);
            Route::post('logout-all', [AuthController::class, 'logoutAll']);
        });

        // Staff & config (owner only — Gate::before handles it)
        Route::apiResource('users',             UserController::class);
        Route::apiResource('operatories',       OperatoryController::class);
        Route::apiResource('appointment-types', AppointmentTypeController::class);
        Route::apiResource('procedure-codes',   ProcedureCodeController::class);
        Route::apiResource('payment-methods',   PaymentMethodController::class);

        // Providers
        Route::apiResource('providers', ProviderController::class);

        // ── Patients ──────────────────────────────────────────────────────────
        Route::middleware('token.ability:patients:read')->group(function () {
            Route::get('patients/{patient}/timeline', PatientTimelineController::class)->name('patients.timeline');
            Route::get('patients/{patient}/ledger',   [InvoiceController::class, 'ledger'])->name('patients.ledger');
            Route::apiResource('patients', PatientController::class)->only(['index', 'show']);

            // Phase 2A — structured sub-tables (read)
            Route::apiResource('patients/{patient}/contacts',    PatientContactController::class)->only(['index', 'show'])->shallow();
            Route::apiResource('patients/{patient}/allergies',   PatientAllergyController::class)->only(['index', 'show'])->shallow();
            Route::apiResource('patients/{patient}/conditions',  PatientConditionController::class)->only(['index', 'show'])->shallow();
            Route::apiResource('patients/{patient}/medications', PatientMedicationController::class)->only(['index', 'show'])->shallow();
            Route::apiResource('patients/{patient}/consents',    PatientConsentController::class)->only(['index', 'show'])->shallow();

            // Phase 2B — medical cases and documents (read)
            Route::apiResource('patients/{patient}/medical-cases', PatientMedicalCaseController::class)
                ->only(['index', 'show'])
                ->parameter('medical-cases', 'medicalCase');

            Route::get('patients/{patient}/documents/{document}/download',
                [PatientMedicalDocumentController::class, 'download']
            )->name('patients.documents.download');
            Route::apiResource('patients/{patient}/documents', PatientMedicalDocumentController::class)
                ->only(['index', 'show'])
                ->parameter('documents', 'document');
        });

        Route::middleware('token.ability:patients:write')->group(function () {
            Route::post('patients/{patient}/archive', [PatientController::class, 'archive'])->withTrashed();
            Route::post('patients/{patient}/restore', [PatientController::class, 'restore'])->withTrashed();
            Route::apiResource('patients', PatientController::class)->only(['store', 'update', 'destroy']);

            // Phase 2A — write
            Route::apiResource('patients/{patient}/contacts',    PatientContactController::class)->only(['store', 'update', 'destroy'])->shallow();
            Route::apiResource('patients/{patient}/allergies',   PatientAllergyController::class)->only(['store', 'update', 'destroy'])->shallow();
            Route::apiResource('patients/{patient}/conditions',  PatientConditionController::class)->only(['store', 'update', 'destroy'])->shallow();
            Route::apiResource('patients/{patient}/medications', PatientMedicationController::class)->only(['store', 'update', 'destroy'])->shallow();
            Route::apiResource('patients/{patient}/consents',    PatientConsentController::class)->only(['store', 'update', 'destroy'])->shallow();

            // Phase 2B-1 — medical cases write
            Route::apiResource('patients/{patient}/medical-cases', PatientMedicalCaseController::class)
                ->only(['store', 'update', 'destroy'])
                ->parameter('medical-cases', 'medicalCase');
        });

        // Phase 2B-2 — document upload (assistants can upload too)
        Route::middleware('token.ability:documents:write')->group(function () {
            Route::apiResource('patients/{patient}/documents', PatientMedicalDocumentController::class)
                ->only(['store', 'update', 'destroy'])
                ->parameter('documents', 'document');
        });

        // ── Appointments ──────────────────────────────────────────────────────
        Route::middleware('token.ability:appointments:read')->group(function () {
            Route::apiResource('appointments', AppointmentController::class)->only(['index', 'show']);
        });

        Route::middleware('token.ability:appointments:write')->group(function () {
            Route::apiResource('appointments', AppointmentController::class)->only(['store', 'update', 'destroy']);
        });

        // ── Schedule blocks ───────────────────────────────────────────────────
        Route::middleware('token.ability:schedule-blocks:read')->group(function () {
            Route::apiResource('schedule-blocks', ScheduleBlockController::class)->only(['index', 'show']);
        });

        Route::middleware('token.ability:schedule-blocks:write')->group(function () {
            Route::apiResource('schedule-blocks', ScheduleBlockController::class)->only(['store', 'update', 'destroy']);
        });

        // ── Recalls & communication logs ──────────────────────────────────────
        Route::middleware('token.ability:recalls:read')->group(function () {
            Route::get('recalls/due',                           [RecallController::class, 'due']);
            Route::apiResource('recalls', RecallController::class)->only(['index', 'show']);
            Route::get('communication-logs',                    [CommunicationLogController::class, 'index']);
            Route::get('communication-logs/{communicationLog}', [CommunicationLogController::class, 'show']);
        });

        Route::middleware('token.ability:recalls:write')->group(function () {
            Route::patch('recalls/{recall}/status',       [RecallController::class, 'updateStatus']);
            Route::post('recalls/{recall}/send-reminder', [RecallController::class, 'sendReminder']);
            Route::apiResource('recalls', RecallController::class)->only(['store', 'update', 'destroy']);
        });

        // ── Billing ───────────────────────────────────────────────────────────
        Route::middleware('token.ability:billing:read')->group(function () {
            Route::apiResource('invoices',      InvoiceController::class)->only(['index', 'show']);
            Route::apiResource('invoices/{invoice}/items', InvoiceItemController::class)->only(['index'])->parameter('items', 'item');
            Route::apiResource('payments',      PaymentController::class)->only(['index', 'show']);
            Route::apiResource('payment-plans', PaymentPlanController::class)->only(['index', 'show']);
        });

        Route::middleware('token.ability:billing:write')->group(function () {
            Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize']);
            Route::post('invoices/{invoice}/void',     [InvoiceController::class, 'void']);
            Route::apiResource('invoices',      InvoiceController::class)->only(['store', 'update', 'destroy']);
            Route::apiResource('invoices/{invoice}/items', InvoiceItemController::class)
                ->only(['store', 'update', 'destroy'])->parameter('items', 'item')->shallow();
            Route::apiResource('payments',      PaymentController::class)->only(['store', 'destroy']);
            Route::apiResource('payment-plans', PaymentPlanController::class)->only(['store', 'destroy']);
        });

        // ── Inventory ─────────────────────────────────────────────────────────
        Route::middleware('token.ability:inventory:read')->group(function () {
            Route::get('inventory-items/low-stock',             [InventoryItemController::class, 'lowStock']);
            Route::get('inventory-items/{inventoryItem}/movements', [InventoryItemController::class, 'movements']);
            Route::apiResource('inventory-items', InventoryItemController::class)->only(['index', 'show']);
            Route::apiResource('suppliers',       SupplierController::class)->only(['index', 'show']);
            Route::apiResource('purchase-orders', PurchaseOrderController::class)->only(['index', 'show']);
        });

        Route::middleware('token.ability:inventory:write')->group(function () {
            Route::post('inventory-items/{inventoryItem}/adjust-stock', [InventoryItemController::class, 'adjustStock']);
            Route::apiResource('inventory-items', InventoryItemController::class)->only(['store', 'update', 'destroy']);
            Route::apiResource('suppliers',       SupplierController::class)->only(['store', 'update', 'destroy']);
            Route::post('purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
            Route::post('purchase-orders/{purchaseOrder}/cancel',  [PurchaseOrderController::class, 'cancel']);
            Route::apiResource('purchase-orders', PurchaseOrderController::class)->only(['store', 'destroy']);
        });

        // ── Clinical (provider-facing) ────────────────────────────────────────
        Route::middleware('token.ability:clinical:read')->group(function () {
            // clinical read routes will be wired in Phase 5 (clinical core)
        });

        // ── Dashboard & reports (owner + receptionist — policy-enforced) ──────
        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::prefix('reports')->group(function () {
            Route::get('appointments',       [ReportController::class, 'appointments']);
            Route::get('production',         [ReportController::class, 'production']);
            Route::get('collections',        [ReportController::class, 'collections']);
            Route::get('recall-performance', [ReportController::class, 'recallPerformance']);
            Route::get('inventory',          [ReportController::class, 'inventory']);
        });

        // ── AI analysis (Phase 5A) ───────────────────────────────────────────────
        Route::middleware('token.ability:clinical:write')->group(function () {
            Route::post('patients/{patient}/documents/{document}/analyze', [AiAnalysisController::class, 'analyzeDocument'])
                ->name('patients.documents.analyze');
        });

        Route::middleware('token.ability:clinical:read')->group(function () {
            Route::get('patients/{patient}/ai-results', [AiAnalysisController::class, 'patientResults'])
                ->name('patients.ai-results.index');
        });

        // AI results actions — no ability middleware, policy handles it
        // SOAP suggestion — clinical:write required
        Route::middleware('token.ability:clinical:write')->group(function () {
            Route::post('encounters/{encounter}/suggest-soap', [AiAnalysisController::class, 'suggestSoap'])
                ->name('encounters.suggest-soap');
        });

        Route::get('ai-results/{result}',           [AiAnalysisController::class, 'show']);
        Route::post('ai-results/{result}/accept',   [AiAnalysisController::class, 'accept']);
        Route::post('ai-results/{result}/dismiss',  [AiAnalysisController::class, 'dismiss']);
        Route::post('ai-results/{result}/apply-soap', [AiAnalysisController::class, 'applySoap']);

        // ── Audit logs (owner only — policy-enforced) ─────────────────────────
        Route::get('audit-logs',            [AuditLogController::class, 'index']);
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    });
});
