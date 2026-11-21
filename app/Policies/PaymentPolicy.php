<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine if the user can view the payment list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine if the user can view the given payment.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $user->isActive() && ($user->isAdmin() || $user->isStaff());
    }

    /**
     * Determine if the user can record payments.
     */
    public function create(User $user): bool
    {
        return $user->isActive() && $user->isAdmin();
    }

    /**
     * Payments are immutable once recorded.
     */
    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Payments are immutable once recorded.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
