<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a small set of realistic customers.
     */
    public function run(): void
    {
        foreach ($this->customers() as $customer) {
            Customer::query()->updateOrCreate(
                ['email' => $customer['email']],
                $customer,
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function customers(): array
    {
        return [
            [
                'name' => 'Green Valley Retail',
                'contact_person' => 'Fatima Sheikh',
                'email' => 'procurement@greenvalley.test',
                'phone' => '+92 42 555 0404',
                'address' => '22 Mall Road, Lahore',
                'is_active' => true,
            ],
            [
                'name' => 'Metro Stationery Co.',
                'contact_person' => 'Usman Ali',
                'email' => 'orders@metrostationery.test',
                'phone' => '+92 21 555 0505',
                'address' => '9 Business Avenue, Karachi',
                'is_active' => true,
            ],
            [
                'name' => 'Home Essentials Ltd.',
                'contact_person' => 'Amina Yusuf',
                'email' => 'sales@homeessentials.test',
                'phone' => '+92 51 555 0606',
                'address' => '16 Central Boulevard, Islamabad',
                'is_active' => true,
            ],
        ];
    }
}
