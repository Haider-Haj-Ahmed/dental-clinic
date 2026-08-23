<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AiAssistantController;

/*
|--------------------------------------------------------------------------
| Web Routes — Dental Clinic PMS
|--------------------------------------------------------------------------
| Completely separate from routes/api.php.
| No Api\ controllers are imported or called here.
*/

/* ══════════════════════════════════════════════════════════════════
 * AUTH ROUTES (session-based, no Sanctum)
 * Laravel's default auth scaffolding hooks — compatible with Breeze
 * if installed, otherwise wire up manually.
 * ══════════════════════════════════════════════════════════════════ */
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', [App\Http\Controllers\Web\Auth\LoginController::class, 'store'])->name('login.store');
});

Route::post('/logout', [App\Http\Controllers\Web\Auth\LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

/* ══════════════════════════════════════════════════════════════════
 * DASHBOARD (protected)
 * ══════════════════════════════════════════════════════════════════ */
Route::prefix('dashboard')
    ->middleware(['auth'])           // removed 'verified' — seeder now sets email_verified_at
    ->name('web.')
    ->group(function () {

        /* ── Stubs — replace with dedicated Web controllers per screen ── */
        Route::get('/',                    fn () => redirect()->route('web.ai'))->name('dashboard');
        Route::get('/appointments',        fn () => view('web.appointments.index'))->name('appointments');
        Route::get('/appointments/create', fn () => view('web.appointments.create'))->name('appointments.create');
        Route::get('/patients',            fn () => view('web.patients.index'))->name('patients');
        Route::get('/recalls',             fn () => view('web.recalls.index'))->name('recalls');
        Route::get('/encounters',          fn () => view('web.encounters.index'))->name('encounters');
        Route::get('/encounters/{id}',     fn ($id) => view('web.encounters.show', compact('id')))->name('encounters.show');
        Route::get('/odontogram',          fn () => view('web.odontogram.index'))->name('odontogram');
        Route::get('/perio',               fn () => view('web.perio.index'))->name('perio');
        Route::get('/treatment-plans',     fn () => view('web.treatment-plans.index'))->name('treatment-plans');
        Route::get('/prescriptions',       fn () => view('web.prescriptions.index'))->name('prescriptions');
        Route::get('/invoices',            fn () => view('web.invoices.index'))->name('invoices');
        Route::get('/payments',            fn () => view('web.payments.index'))->name('payments');
        Route::get('/inventory',           fn () => view('web.inventory.index'))->name('inventory');
        Route::get('/reports',             fn () => view('web.reports.index'))->name('reports');

        /* ══════════════════════════════════════════════════════════
         * AI ASSISTANT (fully implemented)
         * ══════════════════════════════════════════════════════════ */
        Route::controller(AiAssistantController::class)->group(function () {

            // Listing & display
            Route::get('/ai',                             'index')->name('ai');
            Route::get('/ai/{result}',                    'show')->name('ai.show');
            Route::get('/patients/{patient}/ai-results',  'patientResults')->name('ai.patient-results');
            Route::get('/patients/{patient}/ai-insights', 'patientInsights')->name('ai.patient-insights');

            // Trigger — generate new AI results
            Route::post('/patients/{patient}/documents/{document}/analyze', 'analyzeDocument')->name('ai.analyze-document');
            Route::post('/encounters/{encounter}/suggest-soap',             'suggestSoap')->name('ai.suggest-soap');
            Route::post('/patients/{patient}/prescription-suggestions',     'suggestPrescription')->name('ai.suggest-prescription');
            Route::post('/perio-exams/{perioExam}/risk-score',              'scorePerioRisk')->name('ai.score-perio');
            Route::post('/ai/recalls/prioritize',                           'prioritiseRecalls')->name('ai.prioritize-recalls');

            // Review — accept / apply / dismiss
            Route::patch('/ai/{result}/accept',              'accept')->name('ai.accept');
            Route::patch('/ai/{result}/dismiss',             'dismiss')->name('ai.dismiss');
            Route::post('/ai/{result}/apply-soap',           'applySoap')->name('ai.apply-soap');
            Route::post('/ai/{result}/create-prescription',  'createPrescription')->name('ai.create-prescription');
        });
    });

/* Root → redirect to dashboard or login */
Route::get('/', fn () => auth()->check()
    ? redirect()->route('web.ai')
    : redirect()->route('login')
);
