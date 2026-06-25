<?php

namespace App\Policies;

use App\Models\Prescription;
use App\Models\User;

class PrescriptionPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST, User::ROLE_PROVIDER, User::ROLE_ASSISTANT]); }
    public function view(User $user, Prescription $prescription): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_PROVIDER]); }
    public function update(User $user, Prescription $prescription): bool
    {
        if ($user->isOwner()) return true;
        return $user->isProvider() && $prescription->provider_id === $user->providerProfile?->id;
    }
    public function delete(User $user, Prescription $prescription): bool { return $this->update($user, $prescription); }
}
