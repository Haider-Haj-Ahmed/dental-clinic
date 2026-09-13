<?php

namespace App\Providers;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

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
