<?php

namespace App\Policies;

use App\Models\PatientAllergy;
use App\Models\User;

class PatientAllergyPolicy extends PatientSubResourcePolicy
{
    public function viewAny(User $user): bool { return $this->canView($user); }
    public function view(User $user, PatientAllergy $record): bool { return $this->canView($user); }
    public function create(User $user): bool  { return $this->canWrite($user); }
    public function update(User $user, PatientAllergy $record): bool { return $this->canWrite($user); }
    public function delete(User $user, PatientAllergy $record): bool { return $this->canWrite($user); }
}
