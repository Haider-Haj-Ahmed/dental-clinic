<?php

namespace App\Policies;

use App\Models\TreatmentPlan;
use App\Models\User;

class TreatmentPlanPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST, User::ROLE_PROVIDER, User::ROLE_ASSISTANT]); }
    public function view(User $user, TreatmentPlan $plan): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_PROVIDER]); }
    public function update(User $user, TreatmentPlan $plan): bool
    {
        if ($plan->status === TreatmentPlan::STATUS_ACCEPTED || $plan->status === TreatmentPlan::STATUS_REJECTED) return false;
        if ($user->isOwner()) return true;
        return $user->isProvider() && $plan->provider_id === $user->providerProfile?->id;
    }
    public function delete(User $user, TreatmentPlan $plan): bool
    {
        return $user->isOwner() && $plan->status === TreatmentPlan::STATUS_DRAFT;
    }
}
