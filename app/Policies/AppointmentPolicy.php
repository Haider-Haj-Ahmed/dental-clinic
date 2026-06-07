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
            User::ROLE_ASSISTANT,
        ]);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->isOwner() || $user->isReceptionist()) {
            return true;
        }

        $providerId = $user->providerProfile?->id;

        return $providerId !== null && $appointment->provider_id === $providerId;
    }

    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isReceptionist();
    }

    /**
     * Providers may only update clinical notes on their own appointments.
     * Receptionists and owners may update everything.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        if ($user->isOwner() || $user->isReceptionist()) {
            return true;
        }

        // Provider: only their own appointment, and only notes field
        // (enforced in UpdateAppointmentRequest — provider payload stripped to notes only)
        $providerId = $user->providerProfile?->id;

        return $providerId !== null && $appointment->provider_id === $providerId;
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->isOwner() || $user->isReceptionist();
    }
}
