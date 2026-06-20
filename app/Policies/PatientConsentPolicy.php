<?php

namespace App\Policies;

use App\Models\PatientConsent;
use App\Models\User;

class PatientConsentPolicy extends PatientSubResourcePolicy
{
    public function viewAny(User $user): bool { return $this->canView($user); }
    public function view(User $user, PatientConsent $record): bool { return $this->canView($user); }
    public function create(User $user): bool  { return $this->canWrite($user); }
    public function update(User $user, PatientConsent $record): bool { return $this->canWrite($user); }
    public function delete(User $user, PatientConsent $record): bool { return $this->canWrite($user); }
}
