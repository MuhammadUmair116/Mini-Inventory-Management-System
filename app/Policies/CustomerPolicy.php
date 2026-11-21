<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine if the user can view the customer list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine if the user can view the given customer.
     */
    public function view(User $user, Customer $customer): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine if the user can create customers.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can update the given customer.
     */
    public function update(User $user, Customer $customer): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can delete the given customer.
     */
    public function delete(User $user, Customer $customer): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
