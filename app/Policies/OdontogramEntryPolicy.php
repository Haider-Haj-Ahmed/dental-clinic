<?php

namespace App\Policies;

use App\Models\OdontogramEntry;
use App\Models\User;

class OdontogramEntryPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST, User::ROLE_PROVIDER, User::ROLE_ASSISTANT]); }
    public function view(User $user, OdontogramEntry $entry): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_PROVIDER]); }
    public function update(User $user, OdontogramEntry $entry): bool
    {
        if ($user->isOwner()) return true;
        return $user->isProvider() && $entry->provider_id === $user->providerProfile?->id;
    }
    public function delete(User $user, OdontogramEntry $entry): bool { return $this->update($user, $entry); }
}
