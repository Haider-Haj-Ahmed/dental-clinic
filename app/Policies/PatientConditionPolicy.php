<?php

namespace App\Policies;

use App\Models\PatientCondition;
use App\Models\User;

class PatientConditionPolicy extends PatientSubResourcePolicy
{
    public function viewAny(User $user): bool { return $this->canView($user); }
    public function view(User $user, PatientCondition $record): bool { return $this->canView($user); }
    public function create(User $user): bool  { return $this->canWrite($user); }
    public function update(User $user, PatientCondition $record): bool { return $this->canWrite($user); }
    public function delete(User $user, PatientCondition $record): bool { return $this->canWrite($user); }
}
