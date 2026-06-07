<?php

namespace App\Policies;

use App\Models\AppointmentType;
use App\Models\User;

class AppointmentTypePolicy
{
    public function viewAny(User $user): bool { return true; }
    public function view(User $user, AppointmentType $type): bool { return true; }
    public function create(User $user): bool  { return $user->isOwner(); }
    public function update(User $user, AppointmentType $type): bool { return $user->isOwner(); }
    public function delete(User $user, AppointmentType $type): bool { return $user->isOwner(); }
}
