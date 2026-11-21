<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sale>
 */
class SaleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'invoice_number' => fake()->unique()->numerify('SAL-#####'),
            'sale_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'status' => Sale::STATUS_COMPLETED,
            'notes' => fake()->optional()->sentence(),
            'subtotal_amount' => '0.00',
            'discount_amount' => '0.00',
            'total_amount' => '0.00',
            'created_by' => User::factory(),
        ];
    }
}
