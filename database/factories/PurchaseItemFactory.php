<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PurchaseItem>
 */
class PurchaseItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 25);
        $unitPrice = fake()->randomFloat(2, 100, 5000);

        return [
            'purchase_id' => Purchase::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'sku' => Str::upper(Str::random(3).'-'.fake()->unique()->randomNumber(5)),
            'unit_short_name' => 'PCS',
            'quantity' => number_format($quantity, 3, '.', ''),
            'unit_price' => number_format($unitPrice, 2, '.', ''),
            'line_total' => number_format($quantity * $unitPrice, 2, '.', ''),
        ];
    }
}
