<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->randomFloat(2, 100, 10000);

        return [
            'category_id' => Category::factory(),
            'unit_id' => Unit::factory(),
            'name' => fake()->unique()->words(2, true).' '.Str::upper(Str::random(3)),
            'sku' => Str::upper(Str::random(3).'-'.fake()->unique()->randomNumber(5)),
            'barcode' => null,
            'description' => fake()->optional()->sentence(),
            'purchase_price' => number_format($purchasePrice, 2, '.', ''),
            'sale_price' => number_format($purchasePrice * 1.25, 2, '.', ''),
            'current_stock' => number_format(fake()->randomFloat(3, 0, 200), 3, '.', ''),
            'reorder_level' => number_format(fake()->randomFloat(3, 5, 30), 3, '.', ''),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the product has no stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => '0.000',
        ]);
    }

    /**
     * Indicate that the product is below its reorder level.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_stock' => '5.000',
            'reorder_level' => '10.000',
        ]);
    }
}
