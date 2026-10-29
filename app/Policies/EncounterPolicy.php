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
        // is_locked is intentionally NOT checked here — the controller returns
        // a 422 with a descriptive message, which is more appropriate than a
        // generic 403 for a locked record.
        if ($user->isOwner()) return true;
        return $user->isProvider() && $encounter->provider_id === $user->providerProfile?->id;
    }

    public function delete(User $user, Encounter $encounter): bool
    {
        // is_locked handled in controller with 422.
        return $user->isOwner();
    }

    public function lock(User $user, Encounter $encounter): bool
    {
        return $user->isOwner() || ($user->isProvider() && $encounter->provider_id === $user->providerProfile?->id);
    }
}
