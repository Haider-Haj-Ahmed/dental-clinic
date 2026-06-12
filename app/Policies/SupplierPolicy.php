<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy extends InventoryPolicy
{
    public function view(User $user, Supplier $model): bool { return $this->viewAny($user); }
    public function update(User $user, Supplier $model): bool { return parent::update($user); }
    public function delete(User $user, Supplier $model): bool { return parent::delete($user); }
}
