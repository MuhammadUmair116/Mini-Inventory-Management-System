<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    /**
     * Determine if the user can view the sale list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine if the user can view the given sale.
     */
    public function view(User $user, Sale $sale): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine if the user can record sales.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Sales are immutable once recorded.
     */
    public function update(User $user, Sale $sale): bool
    {
        return false;
    }

    /**
     * Sales are immutable once recorded.
     */
    public function delete(User $user, Sale $sale): bool
    {
        return false;
    }
}
