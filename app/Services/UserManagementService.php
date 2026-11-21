<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    /**
     * Create an account with a hashed password and verified email address.
     *
     * @param  array{name: string, email: string, password: string, role: string, status: string}  $data
     */
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            return User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'status' => $data['status'],
                'email_verified_at' => now(),
            ]);
        });
    }

    /**
     * Update an account without ever removing the last active administrator.
     *
     * @param  array{name: string, email: string, role: string, status: string, password?: string|null}  $data
     */
    public function update(User $user, User $actor, array $data): User
    {
        return DB::transaction(function () use ($user, $actor, $data): User {
            $target = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $this->ensureLastActiveAdminIsPreserved($actor, $target, $data['role'], $data['status']);

            $target->name = $data['name'];
            $target->email = $data['email'];
            $target->role = $data['role'];
            $target->status = $data['status'];

            if (filled($data['password'] ?? null)) {
                $target->password = Hash::make($data['password']);
            }

            $target->save();

            return $target;
        }, attempts: 5);
    }

    /**
     * The actor is included in the count because only active admins can reach this service.
     */
    private function ensureLastActiveAdminIsPreserved(
        User $actor,
        User $target,
        string $role,
        string $status,
    ): void {
        $roleChanged = $target->role !== $role;
        $statusChanged = $target->status !== $status;

        if (! $roleChanged && ! $statusChanged) {
            return;
        }

        if (! $target->isAdmin() || ! $target->isActive()) {
            return;
        }

        $activeAdminCount = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('status', User::STATUS_ACTIVE)
            ->lockForUpdate()
            ->count();

        if ($activeAdminCount > 1) {
            return;
        }

        throw ValidationException::withMessages([
            'role' => 'At least one active administrator must remain.',
            'status' => 'At least one active administrator must remain.',
        ]);
    }
}
