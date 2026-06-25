<?php

namespace App\Policies;

use App\Models\Encounter;
use App\Models\User;

class EncounterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST, User::ROLE_PROVIDER, User::ROLE_ASSISTANT]);
    }

    public function view(User $user, Encounter $encounter): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_PROVIDER]);
    }

    public function update(User $user, Encounter $encounter): bool
    {
        if ($encounter->is_locked) return false;
        if ($user->isOwner()) return true;
        return $user->isProvider() && $encounter->provider_id === $user->providerProfile?->id;
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        if ($encounter->is_locked) return false;
        return $user->isOwner();
    }

    public function lock(User $user, Encounter $encounter): bool
    {
        return $user->isOwner() || ($user->isProvider() && $encounter->provider_id === $user->providerProfile?->id);
    }
}
