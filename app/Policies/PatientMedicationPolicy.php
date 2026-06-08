<?php

namespace App\Policies;

use App\Models\PatientMedication;
use App\Models\User;

class PatientMedicationPolicy extends PatientSubResourcePolicy
{
    public function viewAny(User $user): bool { return $this->canView($user); }
    public function view(User $user, PatientMedication $record): bool { return $this->canView($user); }
    public function create(User $user): bool  { return $this->canWrite($user); }
    public function update(User $user, PatientMedication $record): bool { return $this->canWrite($user); }
    public function delete(User $user, PatientMedication $record): bool { return $this->canWrite($user); }
}
