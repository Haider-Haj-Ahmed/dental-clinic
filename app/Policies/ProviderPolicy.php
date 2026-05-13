<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
        ]);
    }

    public function view(User $user, Provider $provider): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->isOwner();
    }

    public function update(User $user, Provider $provider): bool
    {
        return $user->isOwner();
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $user->isOwner();
    }
}
