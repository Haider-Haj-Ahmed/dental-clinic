<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\User;
use App\Policies\AppointmentPolicy;
use App\Policies\PatientPolicy;
use App\Policies\ProviderPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Patient::class, PatientPolicy::class);
        Gate::policy(Provider::class, ProviderPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);

        Gate::before(function (User $user) {
            return $user->isOwner() ? true : null;
        });
    }
}
