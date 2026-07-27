<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\AiAssistantController;

/*
|--------------------------------------------------------------------------
| Web Routes — Dental Clinic PMS Dashboard
|--------------------------------------------------------------------------
|
| These routes render Blade views for the staff dashboard UI.
| They are completely separate from routes/api.php and do NOT
| modify any Api\ controller or their response flow.
|
| All routes are protected by auth + verified middleware.
| Prefix: /dashboard
|
*/

Route::prefix('dashboard')
    ->middleware(['auth', 'verified'])
    ->name('web.')
    ->group(function () {

        /* ── Placeholder stubs ─────────────────────────────
         * Replace each closure with a dedicated Web controller
         * as those views are implemented in future sessions.
         */
        Route::get('/',             fn () => view('web.dashboard.index'))->name('dashboard');
        Route::get('/appointments', fn () => view('web.appointments.index'))->name('appointments');
        Route::get('/appointments/create', fn () => view('web.appointments.create'))->name('appointments.create');
        Route::get('/patients',     fn () => view('web.patients.index'))->name('patients');
        Route::get('/recalls',      fn () => view('web.recalls.index'))->name('recalls');

        Route::get('/encounters',         fn () => view('web.encounters.index'))->name('encounters');
        Route::get('/encounters/{id}',    fn ($id) => view('web.encounters.show', ['id' => $id]))->name('encounters.show');
        Route::get('/odontogram',         fn () => view('web.odontogram.index'))->name('odontogram');
        Route::get('/perio',              fn () => view('web.perio.index'))->name('perio');
        Route::get('/treatment-plans',    fn () => view('web.treatment-plans.index'))->name('treatment-plans');
        Route::get('/prescriptions',      fn () => view('web.prescriptions.index'))->name('prescriptions');

        Route::get('/invoices', fn () => view('web.invoices.index'))->name('invoices');
        Route::get('/payments', fn () => view('web.payments.index'))->name('payments');

        Route::get('/inventory', fn () => view('web.inventory.index'))->name('inventory');
        Route::get('/reports',   fn () => view('web.reports.index'))->name('reports');

        /* ── AI Assistant (implemented) ──────────────────── */
        Route::controller(AiAssistantController::class)
            ->prefix('ai')
            ->name('ai')
            ->group(function () {
                Route::get('/',                   'index')->name('');           // web.ai
                Route::get('/{result}',           'show')->name('.show');       // web.ai.show
                Route::patch('/{result}/accept',  'accept')->name('.accept');   // web.ai.accept
                Route::patch('/{result}/dismiss', 'dismiss')->name('.dismiss'); // web.ai.dismiss
            });

    });
