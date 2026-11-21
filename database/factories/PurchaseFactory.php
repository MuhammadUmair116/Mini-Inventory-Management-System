<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'invoice_number' => fake()->unique()->numerify('PUR-#####'),
            'purchase_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'notes' => fake()->optional()->sentence(),
            'total_amount' => '0.00',
            'created_by' => User::factory(),
        ];
    }
}
