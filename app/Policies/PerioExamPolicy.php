<?php

namespace App\Policies;

use App\Models\PerioExam;
use App\Models\User;

class PerioExamPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST, User::ROLE_PROVIDER, User::ROLE_ASSISTANT]); }
    public function view(User $user, PerioExam $exam): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_PROVIDER]); }
    public function update(User $user, PerioExam $exam): bool
    {
        if ($user->isOwner()) return true;
        return $user->isProvider() && $exam->provider_id === $user->providerProfile?->id;
    }
    public function delete(User $user, PerioExam $exam): bool { return $this->update($user, $exam); }
}
