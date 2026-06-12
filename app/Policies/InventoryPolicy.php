<?php

namespace App\Policies;

use App\Models\User;

/**
 * Shared policy for all inventory resources.
 * Only owner manages inventory — providers and receptionists are read-only.
 */
class InventoryPolicy
{
    public function viewAny(User $user): bool  { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST, User::ROLE_PROVIDER]); }
    public function view(User $user): bool     { return $this->viewAny($user); }
    public function create(User $user): bool   { return $user->isOwner() || $user->isReceptionist(); }
    public function update(User $user): bool   { return $user->isOwner() || $user->isReceptionist(); }
    public function delete(User $user): bool   { return $user->isOwner(); }
}
