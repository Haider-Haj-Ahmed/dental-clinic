<?php

namespace App\Providers;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Laravel\Reverb\ApplicationManagerServiceProvider;
use Laravel\Reverb\ReverbServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Laravel 13 blocks DevCommands registration from vendor packages.
        // Register Reverb from app code so package discovery can stay disabled
        // and `reverb:start` / `php artisan dev` still work.
        $this->app->register(ApplicationManagerServiceProvider::class);
        $this->app->register(ReverbServiceProvider::class);
    }

    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->isOwner()) {
                return true;
            }
        });

        Event::listen(function (Registered $event) {
            $user = $event->user;
            if (! $user->hasVerifiedEmail()) {
                Mail::to($user->email, $user->name)->queue(new VerifyEmailMail($user));
            }
        });
    }
}
