<?php

namespace App\Policies;

use App\Models\PatientMedicalCase;
use App\Models\User;

class PatientMedicalCasePolicy
{
    // All staff can view cases
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
            User::ROLE_ASSISTANT,
        ]);
    }

    public function view(User $user, PatientMedicalCase $case): bool
    {
        return $this->viewAny($user);
    }

    // Any provider, receptionist, or owner can create a case
    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
        ]);
    }

    // Providers can only update cases they created; owner/receptionist can update any
    public function update(User $user, PatientMedicalCase $case): bool
    {
        if ($user->isOwner() || $user->isReceptionist()) {
            return true;
        }

        return $user->isProvider() && $case->created_by === $user->id;
    }

    // Same rule as update for deletion
    public function delete(User $user, PatientMedicalCase $case): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->isProvider() && $case->created_by === $user->id;
    }
}
