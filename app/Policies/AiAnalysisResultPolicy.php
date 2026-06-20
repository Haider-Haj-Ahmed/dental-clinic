<?php

namespace App\Policies;

use App\Models\AiAnalysisResult;
use App\Models\User;

class AiAnalysisResultPolicy
{
    // All clinical staff can view results
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_OWNER,
            User::ROLE_RECEPTIONIST,
            User::ROLE_PROVIDER,
        ]);
    }

    public function view(User $user, AiAnalysisResult $result): bool
    {
        return $this->viewAny($user);
    }

    // Only providers and owners can trigger analysis
    public function create(User $user): bool
    {
        return $user->isOwner() || $user->isProvider();
    }

    // Only the requesting provider or owner can review (accept/dismiss)
    public function review(User $user, AiAnalysisResult $result): bool
    {
        if ($user->isOwner()) {
            return true;
        }

        return $user->isProvider() && $result->requested_by === $user->id;
    }
}
