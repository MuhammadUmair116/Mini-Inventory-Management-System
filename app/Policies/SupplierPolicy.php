<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;

class SupplierPolicy
{
    /**
     * Determine if the user can view the supplier list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can view the given supplier.
     */
    public function view(User $user, Supplier $supplier): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can create suppliers.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can update the given supplier.
     */
    public function update(User $user, Supplier $supplier): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can delete the given supplier.
     */
    public function delete(User $user, Supplier $supplier): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
