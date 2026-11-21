<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    /**
     * Determine if the user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can view the given category.
     */
    public function view(User $user, Category $category): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can update the given category.
     */
    public function update(User $user, Category $category): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can delete the given category.
     */
    public function delete(User $user, Category $category): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
