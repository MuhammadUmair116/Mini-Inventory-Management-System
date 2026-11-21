<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity' => '5.000',
            'balance_after' => '10.000',
            'reference_type' => Purchase::class,
            'reference_id' => Purchase::factory(),
            'notes' => null,
            'created_by' => User::factory(),
        ];
    }
}
