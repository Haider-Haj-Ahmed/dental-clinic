<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
        ]);
    }

    public function view(User $user, Patient $patient): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
        ]);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $this->create($user);
    }

    public function restore(User $user, Patient $patient): bool
    {
        return $this->create($user);
    }
}
