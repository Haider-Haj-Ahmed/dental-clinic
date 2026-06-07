<?php

namespace App\Policies;

use App\Models\ScheduleBlock;
use App\Models\User;

class ScheduleBlockPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST, User::ROLE_PROVIDER]); }
    public function view(User $user, ScheduleBlock $block): bool { return $this->viewAny($user); }
    public function create(User $user): bool  { return $user->isOwner() || $user->isReceptionist(); }
    public function update(User $user, ScheduleBlock $block): bool { return $user->isOwner() || $user->isReceptionist(); }
    public function delete(User $user, ScheduleBlock $block): bool { return $user->isOwner() || $user->isReceptionist(); }
}
