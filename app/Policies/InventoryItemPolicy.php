<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy extends InventoryPolicy
{
    public function view(User $user, InventoryItem $model): bool { return $this->viewAny($user); }
    public function update(User $user, InventoryItem $model): bool { return parent::update($user); }
    public function delete(User $user, InventoryItem $model): bool { return parent::delete($user); }
}
