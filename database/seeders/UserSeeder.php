<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the demo accounts used by the application.
     */
    public function run(): void
    {
        foreach ($this->accounts() as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'role' => $account['role'],
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(),
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, email: string, password: string, role: string}>
     */
    private function accounts(): array
    {
        return [
            [
                'name' => 'Inventory Admin',
                'email' => 'admin@inventory.test',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Inventory Staff',
                'email' => 'staff@inventory.test',
                'password' => 'password',
                'role' => User::ROLE_STAFF,
            ],
        ];
    }
}
