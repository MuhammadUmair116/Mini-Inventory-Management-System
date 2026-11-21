<?php

namespace App\Policies;

use App\Models\Purchase;
use App\Models\User;

class PurchasePolicy
{
    /**
     * Determine if the user can view the purchase list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can view the given purchase.
     */
    public function view(User $user, Purchase $purchase): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can record purchases.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
