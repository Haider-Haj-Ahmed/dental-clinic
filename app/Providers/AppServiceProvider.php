<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\CommunicationLog;
use App\Models\Invoice;
use App\Models\InventoryItem;
use App\Models\Operatory;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientConsent;
use App\Models\PatientContact;
use App\Models\PatientMedicalCase;
use App\Models\PatientMedicalDocument;
use App\Models\PatientMedication;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\PaymentPlan;
use App\Models\ProcedureCode;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\Recall;
use App\Models\ScheduleBlock;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\AuditObserver;
use App\Policies\AppointmentPolicy;
use App\Policies\AppointmentTypePolicy;
use App\Policies\AiAnalysisResultPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\CommunicationLogPolicy;
use App\Policies\InventoryItemPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\OperatoryPolicy;
use App\Policies\PatientAllergyPolicy;
use App\Policies\PatientConditionPolicy;
use App\Policies\PatientConsentPolicy;
use App\Policies\PatientContactPolicy;
use App\Policies\PatientMedicalCasePolicy;
use App\Policies\PatientMedicalDocumentPolicy;
use App\Policies\PatientMedicationPolicy;
use App\Policies\PatientPolicy;
use App\Policies\PaymentMethodPolicy;
use App\Policies\PaymentPlanPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\ProcedureCodePolicy;
use App\Policies\ProviderPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\RecallPolicy;
use App\Policies\ScheduleBlockPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\UserPolicy;
use App\Models\AiAnalysisResult;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Policies ──────────────────────────────────────────────────────────
        Gate::policy(User::class,                   UserPolicy::class);
        Gate::policy(Patient::class,                PatientPolicy::class);
        Gate::policy(Provider::class,               ProviderPolicy::class);
        Gate::policy(Appointment::class,            AppointmentPolicy::class);
        Gate::policy(Operatory::class,              OperatoryPolicy::class);
        Gate::policy(AppointmentType::class,        AppointmentTypePolicy::class);
        Gate::policy(ProcedureCode::class,          ProcedureCodePolicy::class);
        Gate::policy(ScheduleBlock::class,          ScheduleBlockPolicy::class);
        Gate::policy(PatientContact::class,         PatientContactPolicy::class);
        Gate::policy(PatientAllergy::class,         PatientAllergyPolicy::class);
        Gate::policy(PatientCondition::class,       PatientConditionPolicy::class);
        Gate::policy(PatientMedication::class,      PatientMedicationPolicy::class);
        Gate::policy(PatientConsent::class,         PatientConsentPolicy::class);
        Gate::policy(PatientMedicalCase::class,     PatientMedicalCasePolicy::class);
        Gate::policy(PatientMedicalDocument::class, PatientMedicalDocumentPolicy::class);
        Gate::policy(Recall::class,                 RecallPolicy::class);
        Gate::policy(CommunicationLog::class,       CommunicationLogPolicy::class);
        Gate::policy(Invoice::class,                InvoicePolicy::class);
        Gate::policy(Payment::class,                PaymentPolicy::class);
        Gate::policy(PaymentPlan::class,            PaymentPlanPolicy::class);
        Gate::policy(PaymentMethod::class,          PaymentMethodPolicy::class);
        Gate::policy(Supplier::class,               SupplierPolicy::class);
        Gate::policy(InventoryItem::class,          InventoryItemPolicy::class);
        Gate::policy(PurchaseOrder::class,          PurchaseOrderPolicy::class);
        Gate::policy(AuditLog::class,               AuditLogPolicy::class);
        Gate::policy(AiAnalysisResult::class,      AiAnalysisResultPolicy::class);

        // Owner bypasses all policy checks
        Gate::before(function (User $user) {
            return $user->isOwner() ? true : null;
        });

        // ── Observers — models that get audited ───────────────────────────────
        $audited = [
            Patient::class,
            Provider::class,
            Appointment::class,
            Invoice::class,
            Payment::class,
            PatientMedicalCase::class,
            PatientMedicalDocument::class,
            PatientAllergy::class,
            PatientCondition::class,
            PatientMedication::class,
            Recall::class,
            InventoryItem::class,
            PurchaseOrder::class,
            User::class,
            AiAnalysisResult::class,
        ];

        foreach ($audited as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
