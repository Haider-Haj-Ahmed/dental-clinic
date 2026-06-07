<?php

namespace App\Policies;

use App\Models\ProcedureCode;
use App\Models\User;

class ProcedureCodePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, ProcedureCode $code): bool { return true; }
    public function create(User $user): bool  { return $user->isOwner(); }
    public function update(User $user, ProcedureCode $code): bool { return $user->isOwner(); }
    public function delete(User $user, ProcedureCode $code): bool { return $user->isOwner(); }
}
