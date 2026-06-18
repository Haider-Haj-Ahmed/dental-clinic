<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    // Audit logs are read-only and owner-only
    public function viewAny(User $user): bool { return $user->isOwner(); }
    public function view(User $user, AuditLog $log): bool { return $user->isOwner(); }
}
