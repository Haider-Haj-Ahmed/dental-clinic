<?php

namespace App\Policies;

use App\Models\User;

/**
 * Shared policy logic for all patient sub-resources
 * (contacts, allergies, conditions, medications, consents).
 *
 * Viewing: owner, receptionist, provider, assistant
 * Writing: owner, receptionist, provider
 */
abstract class PatientSubResourcePolicy
{
    protected function canView(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
            User::ROLE_ASSISTANT,
        ]);
    }

    protected function canWrite(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
        ]);
    }
}
