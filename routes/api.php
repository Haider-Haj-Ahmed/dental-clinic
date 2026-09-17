<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\AppointmentTypeController;
use App\Http\Controllers\Api\AiAnalysisController;
use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClinicSettingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\TwoFactorController;
use App\Http\Controllers\Api\CommunicationLogController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EncounterController;
use App\Http\Controllers\Api\OdontogramController;
use App\Http\Controllers\Api\PerioExamController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\TreatmentPlanController;
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

    /*
    |──────────────────────────────────────────────────────────────
    | AUTH — public endpoints (throttle:auth = 10 req/min per IP)
    |──────────────────────────────────────────────────────────────
    */
    Route::prefix('auth')->middleware('throttle:auth')->group(function () {
        Route::post('register',        [AuthController::class, 'register']);
        Route::post('login',           [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password',  [AuthController::class, 'resetPassword']);

        // Email verification — signed URL, no auth token required
        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware('signed')
            ->name('api.auth.email.verify');

        // 2FA challenge — public, called after login when requires_2fa: true
        Route::post('2fa/challenge', [AuthController::class, 'twoFactorChallenge']);
    });

    /*
    |──────────────────────────────────────────────────────────────
    | ALL AUTHENTICATED ROUTES (throttle:api)
    |──────────────────────────────────────────────────────────────
    */
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {

        /*── Auth session management ──────────────────────────────*/
        Route::prefix('auth')->group(function () {
            Route::get('me',                [AuthController::class, 'me']);
            Route::post('logout',           [AuthController::class, 'logout']);
            Route::post('logout-all',       [AuthController::class, 'logoutAll']);

            // Active token (session) management
            Route::get('tokens',              [AuthController::class, 'tokens']);
            Route::delete('tokens/{tokenId}', [AuthController::class, 'revokeToken']);

            // Change password (authenticated — does not require forgot-password flow)
            Route::post('change-password', [AuthController::class, 'changePassword']);

            // Resend verification — requires auth token, throttled to 1/min
            Route::post('email/resend', [AuthController::class, 'resendVerification'])
                ->middleware('throttle:1,1');

            // 2FA management — requires verified email
            Route::middleware('verified')->group(function () {
                Route::post('2fa/enable',                    [TwoFactorController::class, 'enable']);
                Route::post('2fa/confirm',                   [TwoFactorController::class, 'confirm']);
                Route::post('2fa/disable',                   [TwoFactorController::class, 'disable']);
                Route::get('2fa/recovery-codes',             [TwoFactorController::class, 'recoveryCodes']);
                Route::post('2fa/recovery-codes/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes']);
            });
        });

        /*── Clinic settings (owner only) ─────────────────────────*/
        Route::get('settings',   [ClinicSettingController::class, 'show']);
        Route::patch('settings', [ClinicSettingController::class, 'update']);

        /*── Staff & config (owner only — Gate::before enforces) ──*/
        Route::apiResource('users',             UserController::class);
        Route::apiResource('operatories',       OperatoryController::class);
        Route::apiResource('appointment-types', AppointmentTypeController::class);
        Route::apiResource('procedure-codes',   ProcedureCodeController::class);
        Route::apiResource('payment-methods',   PaymentMethodController::class);

        /*── Providers ─────────────────────────────────────────────*/
        Route::apiResource('providers', ProviderController::class);

        /*── Patients ──────────────────────────────────────────────*/
        Route::middleware('token.ability:patients:read')->group(function () {
            Route::get('patients/{patient}/timeline', PatientTimelineController::class)->name('patients.timeline');
            Route::get('patients/{patient}/ledger',   [InvoiceController::class, 'ledger'])->name('patients.ledger');
            Route::apiResource('patients', PatientController::class)->only(['index', 'show']);

            Route::apiResource('patients/{patient}/contacts',    PatientContactController::class)->only(['index', 'show'])->shallow();
            Route::apiResource('patients/{patient}/allergies',   PatientAllergyController::class)->only(['index', 'show'])->shallow();
            Route::apiResource('patients/{patient}/conditions',  PatientConditionController::class)->only(['index', 'show'])->shallow();
            Route::apiResource('patients/{patient}/medications', PatientMedicationController::class)->only(['index', 'show'])->shallow();
            Route::apiResource('patients/{patient}/consents',    PatientConsentController::class)->only(['index', 'show'])->shallow();

            Route::apiResource('patients/{patient}/medical-cases', PatientMedicalCaseController::class)
                ->only(['index', 'show'])
                ->parameter('medical-cases', 'medicalCase');

            Route::get('patients/{patient}/documents/{document}/download',
                [PatientMedicalDocumentController::class, 'download']
            )->name('patients.documents.download');

            Route::apiResource('patients/{patient}/documents', PatientMedicalDocumentController::class)
                ->only(['index', 'show'])
                ->parameter('documents', 'document');

            Route::get('patients/{patient}/ai-results',  [AiAnalysisController::class, 'patientResults'])->name('patients.ai-results.index');
            Route::get('patients/{patient}/ai-insights', [AiAnalysisController::class, 'patientInsights'])->name('patients.ai-insights');
        });

        Route::middleware('token.ability:patients:write')->group(function () {
            Route::post('patients/{patient}/archive', [PatientController::class, 'archive'])->withTrashed();
            Route::post('patients/{patient}/restore', [PatientController::class, 'restore'])->withTrashed();
            Route::apiResource('patients', PatientController::class)->only(['store', 'update', 'destroy']);

            Route::apiResource('patients/{patient}/contacts',    PatientContactController::class)->only(['store', 'update', 'destroy'])->shallow();
            Route::apiResource('patients/{patient}/allergies',   PatientAllergyController::class)->only(['store', 'update', 'destroy'])->shallow();
            Route::apiResource('patients/{patient}/conditions',  PatientConditionController::class)->only(['store', 'update', 'destroy'])->shallow();
            Route::apiResource('patients/{patient}/medications', PatientMedicationController::class)->only(['store', 'update', 'destroy'])->shallow();
            Route::apiResource('patients/{patient}/consents',    PatientConsentController::class)->only(['store', 'update', 'destroy'])->shallow();

            Route::apiResource('patients/{patient}/medical-cases', PatientMedicalCaseController::class)
                ->only(['store', 'update', 'destroy'])
                ->parameter('medical-cases', 'medicalCase');
        });

        Route::middleware('token.ability:documents:write')->group(function () {
            Route::apiResource('patients/{patient}/documents', PatientMedicalDocumentController::class)
                ->only(['store', 'update', 'destroy'])
                ->parameter('documents', 'document');
        });

        /*── Appointments ───────────────────────────────────────────*/
        Route::middleware('token.ability:appointments:read')->group(function () {
            Route::apiResource('appointments', AppointmentController::class)->only(['index', 'show']);
        });

        Route::middleware('token.ability:appointments:write')->group(function () {
            Route::apiResource('appointments', AppointmentController::class)->only(['store', 'update', 'destroy']);
        });

        /*── Schedule blocks ────────────────────────────────────────*/
        Route::middleware('token.ability:schedule-blocks:read')->group(function () {
            Route::apiResource('schedule-blocks', ScheduleBlockController::class)->only(['index', 'show']);
        });

        Route::middleware('token.ability:schedule-blocks:write')->group(function () {
            Route::apiResource('schedule-blocks', ScheduleBlockController::class)->only(['store', 'update', 'destroy']);
        });

        /*── Recalls & communication logs ───────────────────────────*/
        Route::middleware('token.ability:recalls:read')->group(function () {
            Route::get('recalls/due', [RecallController::class, 'due']);
            Route::apiResource('recalls', RecallController::class)->only(['index', 'show']);
            Route::get('communication-logs',                    [CommunicationLogController::class, 'index']);
            Route::get('communication-logs/{communicationLog}', [CommunicationLogController::class, 'show']);
        });

        Route::middleware('token.ability:recalls:write')->group(function () {
            Route::patch('recalls/{recall}/status',       [RecallController::class, 'updateStatus']);
            Route::post('recalls/{recall}/send-reminder', [RecallController::class, 'sendReminder']);
            Route::apiResource('recalls', RecallController::class)->only(['store', 'update', 'destroy']);
        });

        /*── Billing ────────────────────────────────────────────────*/
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

        /*── Inventory ──────────────────────────────────────────────*/
        Route::middleware('token.ability:inventory:read')->group(function () {
            Route::get('inventory-items/low-stock',                 [InventoryItemController::class, 'lowStock']);
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

        /*── Clinical ───────────────────────────────────────────────*/
        Route::middleware('token.ability:clinical:read')->group(function () {
            Route::apiResource('encounters',      EncounterController::class)->only(['index', 'show']);
            Route::get('encounters/{encounter}/odontogram-entries', [OdontogramController::class, 'encounterEntries']);
            Route::get('patients/{patient}/odontogram',             [OdontogramController::class, 'patientOdontogram']);
            Route::apiResource('perio-exams',     PerioExamController::class)->only(['index', 'show']);
            Route::apiResource('treatment-plans', TreatmentPlanController::class)->only(['index', 'show']);
            Route::apiResource('prescriptions',   PrescriptionController::class)->only(['index', 'show']);
        });

        Route::middleware('token.ability:clinical:write')->group(function () {
            Route::apiResource('encounters', EncounterController::class)->only(['store', 'update', 'destroy']);
            Route::post('encounters/{encounter}/lock',   [EncounterController::class, 'lock']);
            Route::post('encounters/{encounter}/unlock', [EncounterController::class, 'unlock']);

            Route::post('encounters/{encounter}/odontogram-entries', [OdontogramController::class, 'store']);
            Route::put('odontogram-entries/{odontogramEntry}',       [OdontogramController::class, 'update']);
            Route::delete('odontogram-entries/{odontogramEntry}',    [OdontogramController::class, 'destroy']);

            Route::apiResource('perio-exams', PerioExamController::class)->only(['store', 'update', 'destroy']);
            Route::post('perio-exams/{perioExam}/measures',             [PerioExamController::class, 'storeMeasure']);
            Route::delete('perio-exams/{perioExam}/measures/{measure}', [PerioExamController::class, 'destroyMeasure']);

            Route::apiResource('treatment-plans', TreatmentPlanController::class)->only(['store', 'update', 'destroy']);
            Route::post('treatment-plans/{treatmentPlan}/present', [TreatmentPlanController::class, 'present']);
            Route::post('treatment-plans/{treatmentPlan}/accept',  [TreatmentPlanController::class, 'accept']);
            Route::post('treatment-plans/{treatmentPlan}/reject',  [TreatmentPlanController::class, 'reject']);

            Route::apiResource('prescriptions', PrescriptionController::class)->only(['store', 'update', 'destroy']);

            Route::post('patients/{patient}/documents/{document}/analyze',  [AiAnalysisController::class, 'analyzeDocument'])->name('patients.documents.analyze');
            Route::post('encounters/{encounter}/suggest-soap',               [AiAnalysisController::class, 'suggestSoap'])->name('encounters.suggest-soap');
            Route::post('patients/{patient}/prescription-suggestions',       [AiAnalysisController::class, 'suggestPrescription'])->name('patients.prescription-suggestions');
            Route::post('perio-exams/{perioExam}/risk-score',                [AiAnalysisController::class, 'scorePerioRisk'])->name('perio-exams.risk-score');
            Route::post('recalls/ai-prioritize',                             [AiAnalysisController::class, 'prioritiseRecalls'])->name('recalls.ai-prioritize');

            Route::get('ai-results/{result}',                      [AiAnalysisController::class, 'show']);
            Route::post('ai-results/{result}/accept',              [AiAnalysisController::class, 'accept']);
            Route::post('ai-results/{result}/dismiss',             [AiAnalysisController::class, 'dismiss']);
            Route::post('ai-results/{result}/apply-soap',          [AiAnalysisController::class, 'applySoap']);
            Route::post('ai-results/{result}/create-prescription', [AiAnalysisController::class, 'createPrescription']);
        });

        /*── Dashboard & reports ────────────────────────────────────*/
        Route::get('dashboard/kpis', [DashboardController::class, 'kpis']);
        Route::prefix('reports')->group(function () {
            Route::get('appointments',       [ReportController::class, 'appointments']);
            Route::get('production',         [ReportController::class, 'production']);
            Route::get('collections',        [ReportController::class, 'collections']);
            Route::get('recall-performance', [ReportController::class, 'recallPerformance']);
            Route::get('inventory',          [ReportController::class, 'inventory']);
        });

        /*── In-app notifications ──────────────────────────────────*/
        Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
            Route::get('/',               'index');
            Route::post('read-all',       'markAllRead');
            Route::delete('read',         'destroyRead');
            Route::post('{id}/read',      'markRead');
            Route::delete('{id}',         'destroy');
        });

        /*── Audit logs (owner only — policy-enforced) ──────────────*/
        Route::get('audit-logs',            [AuditLogController::class, 'index']);
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    });
});
