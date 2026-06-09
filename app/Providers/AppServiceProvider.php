<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\AppointmentType;
use App\Models\Operatory;
use App\Models\Patient;
use App\Models\PatientAllergy;
use App\Models\PatientCondition;
use App\Models\PatientConsent;
use App\Models\PatientContact;
use App\Models\PatientMedicalCase;
use App\Models\PatientMedicalDocument;
use App\Models\PatientMedication;
use App\Models\ProcedureCode;
use App\Models\Provider;
use App\Models\ScheduleBlock;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\AppointmentTypePolicy;
use App\Policies\OperatoryPolicy;
use App\Policies\PatientAllergyPolicy;
use App\Policies\PatientConditionPolicy;
use App\Policies\PatientConsentPolicy;
use App\Policies\PatientContactPolicy;
use App\Policies\PatientMedicalCasePolicy;
use App\Policies\PatientMedicalDocumentPolicy;
use App\Policies\PatientMedicationPolicy;
use App\Policies\PatientPolicy;
use App\Policies\ProcedureCodePolicy;
use App\Policies\ProviderPolicy;
use App\Policies\ScheduleBlockPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(User::class,                  UserPolicy::class);
        Gate::policy(Patient::class,               PatientPolicy::class);
        Gate::policy(Provider::class,              ProviderPolicy::class);
        Gate::policy(Appointment::class,           AppointmentPolicy::class);
        Gate::policy(Operatory::class,             OperatoryPolicy::class);
        Gate::policy(AppointmentType::class,       AppointmentTypePolicy::class);
        Gate::policy(ProcedureCode::class,         ProcedureCodePolicy::class);
        Gate::policy(ScheduleBlock::class,         ScheduleBlockPolicy::class);
        Gate::policy(PatientContact::class,        PatientContactPolicy::class);
        Gate::policy(PatientAllergy::class,        PatientAllergyPolicy::class);
        Gate::policy(PatientCondition::class,      PatientConditionPolicy::class);
        Gate::policy(PatientMedication::class,     PatientMedicationPolicy::class);
        Gate::policy(PatientConsent::class,        PatientConsentPolicy::class);
        Gate::policy(PatientMedicalCase::class,    PatientMedicalCasePolicy::class);
        Gate::policy(PatientMedicalDocument::class, PatientMedicalDocumentPolicy::class);

        Gate::before(function (User $user) {
            return $user->isOwner() ? true : null;
        });
    }
}
