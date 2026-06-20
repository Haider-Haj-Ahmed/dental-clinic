<?php

namespace App\Policies;

use App\Models\PatientContact;
use App\Models\User;

class PatientContactPolicy extends PatientSubResourcePolicy
{
    public function viewAny(User $user): bool { return $this->canView($user); }
    public function view(User $user, PatientContact $record): bool { return $this->canView($user); }
    public function create(User $user): bool  { return $this->canWrite($user); }
    public function update(User $user, PatientContact $record): bool { return $this->canWrite($user); }
    public function delete(User $user, PatientContact $record): bool { return $this->canWrite($user); }
}
