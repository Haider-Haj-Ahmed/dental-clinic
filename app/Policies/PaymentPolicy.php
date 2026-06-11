<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool { return $user->hasAnyRole([User::ROLE_OWNER, User::ROLE_RECEPTIONIST, User::ROLE_PROVIDER]); }
    public function view(User $user, Payment $model): bool { return $this->viewAny($user); }
    public function create(User $user): bool  { return $user->isOwner() || $user->isReceptionist(); }
    public function update(User $user, Payment $model): bool { return $user->isOwner() || $user->isReceptionist(); }
    public function delete(User $user, Payment $model): bool { return $user->isOwner(); }
}
