<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
        ]);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isOwner() || $user->isReceptionist()) {
            return true;
        }

        if (! $user->isProvider()) {
            return false;
        }

        $providerId = $user->providerProfile?->id;

        return $providerId !== null && $appointment->provider_id === $providerId;
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isReceptionist();
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $this->view($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->isOwner() || $user->isReceptionist();
    }
}
