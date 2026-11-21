<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view the user directory.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can view the given account.
     */
    public function view(User $user, User $target): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can create accounts.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can update the given account.
     */
    public function update(User $user, User $target): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
