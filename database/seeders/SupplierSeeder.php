<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a small set of realistic suppliers.
     */
    public function run(): void
    {
        foreach ($this->suppliers() as $supplier) {
            Supplier::query()->updateOrCreate(
                ['email' => $supplier['email']],
                $supplier,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function suppliers(): array
    {
        return [
            [
                'name' => 'Tech World Supplies',
                'contact_person' => 'Ayesha Khan',
                'email' => 'procurement@techworld.test',
                'phone' => '+92 42 555 0101',
                'address' => '12 Gulberg Avenue, Lahore',
                'is_active' => true,
            ],
            [
                'name' => 'Office Hub',
                'contact_person' => 'Bilal Ahmed',
                'email' => 'orders@officehub.test',
                'phone' => '+92 21 555 0202',
                'address' => '45 Corporate Street, Karachi',
                'is_active' => true,
            ],
            [
                'name' => 'Fresh Mart Distributors',
                'contact_person' => 'Sana Iqbal',
                'email' => 'contact@freshmart.test',
                'phone' => '+92 51 555 0303',
                'address' => '8 Market Road, Islamabad',
                'is_active' => true,
            ],
        ];
    }
}
