<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        /**
         * Let administrators bypass ordinary policy checks, but never let the
         * bypass defeat the immutability rules for recorded payments.
         */
        Gate::before(function (User $user, string $ability, array $arguments): ?bool {
            if (! $user->isActive() || ! $user->isAdmin()) {
                return null;
            }

            $subject = $arguments[0] ?? null;

            if ($subject instanceof Payment && in_array($ability, ['update', 'delete'], true)) {
                return null;
            }

            return true;
        });

        Gate::define('view-reports', fn (User $user): bool => $user->isActive()
            && ($user->isAdmin() || $user->isStaff()));

        Gate::define('view-inventory', fn (User $user): bool => $user->isActive()
            && ($user->isAdmin() || $user->isStaff()));
    }
}
