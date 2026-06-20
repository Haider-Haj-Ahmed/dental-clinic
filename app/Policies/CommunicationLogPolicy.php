<?php

namespace App\Policies;

use App\Models\CommunicationLog;
use App\Models\User;

class CommunicationLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
        ]);
    }

    public function view(User $user, CommunicationLog $log): bool { return $this->viewAny($user); }
    // Logs are created by the system only — no manual create/update/delete via API
}
