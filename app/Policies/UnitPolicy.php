<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    /**
     * Determine if the user can view any units.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can view the given unit.
     */
    public function view(User $user, Unit $unit): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can create units.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can update the given unit.
     */
    public function update(User $user, Unit $unit): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can delete the given unit.
     */
    public function delete(User $user, Unit $unit): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
