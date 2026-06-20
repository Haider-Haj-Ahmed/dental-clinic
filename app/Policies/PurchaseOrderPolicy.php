<?php

namespace App\Policies;

use App\Models\PurchaseOrder;
use App\Models\User;

class PurchaseOrderPolicy extends InventoryPolicy
{
    public function view(User $user, PurchaseOrder $model): bool { return $this->viewAny($user); }
    public function update(User $user, PurchaseOrder $model): bool { return parent::update($user); }
    public function delete(User $user, PurchaseOrder $model): bool { return parent::delete($user); }
}
