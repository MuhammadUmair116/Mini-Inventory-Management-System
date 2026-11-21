<?php

namespace App\Policies;

use App\Models\User;

class SettingPolicy
{
    /**
     * Determine if the user can view application settings.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Determine if the user can update application settings.
     */
    public function update(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }
}
