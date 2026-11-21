<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SaleItem>
 */
class SaleItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->randomFloat(3, 1, 15);
        $unitPrice = fake()->randomFloat(2, 100, 5000);
        $discountPercentage = fake()->randomFloat(2, 0, 20);
        $grossTotal = $quantity * $unitPrice;
        $discountAmount = $grossTotal * ($discountPercentage / 100);
        $lineTotal = $grossTotal - $discountAmount;

        return [
            'sale_id' => Sale::factory(),
            'product_id' => Product::factory(),
            'product_name' => fake()->words(2, true),
            'sku' => Str::upper(Str::random(3).'-'.fake()->unique()->randomNumber(5)),
            'unit_short_name' => 'PCS',
            'quantity' => number_format($quantity, 3, '.', ''),
            'unit_price' => number_format($unitPrice, 2, '.', ''),
            'discount_percentage' => number_format($discountPercentage, 2, '.', ''),
            'line_total' => number_format($lineTotal, 2, '.', ''),
        ];
    }
}
