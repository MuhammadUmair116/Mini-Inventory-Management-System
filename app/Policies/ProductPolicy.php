<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine if the user can view the product catalogue.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine if the user can view the given product.
     */
    public function view(User $user, Product $product): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine if the user can create products.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can update the given product.
     */
    public function update(User $user, Product $product): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can delete the given product.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
