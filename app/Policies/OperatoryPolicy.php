<?php

namespace App\Policies;

use App\Models\Operatory;
use App\Models\User;

class OperatoryPolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, Operatory $operatory): bool { return true; }
    public function create(User $user): bool  { return $user->isOwner(); }
    public function update(User $user, Operatory $operatory): bool { return $user->isOwner(); }
    public function delete(User $user, Operatory $operatory): bool { return $user->isOwner(); }
}
