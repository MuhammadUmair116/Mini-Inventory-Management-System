<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a small, realistic product catalogue.
     */
    public function run(): void
    {
        $category = fn (string $slug) => Category::query()->where('slug', $slug)->firstOrFail()->id;
        $unit = fn (string $shortName) => Unit::query()->where('short_name', $shortName)->firstOrFail()->id;

        foreach ($this->products($category, $unit) as $product) {
            Product::query()->updateOrCreate(
                ['sku' => $product['sku']],
                $product,
            );
        }
    }

    /**
     * @param  callable(string): int  $category
     * @param  callable(string): int  $unit
     * @return array<int, array<string, mixed>>
     */
    private function products(callable $category, callable $unit): array
    {
        return [
            [
                'category_id' => $category('electronics'),
                'unit_id' => $unit('PCS'),
                'name' => 'Business Laptop',
                'sku' => 'ELEC-LAP-001',
                'barcode' => '8901000000017',
                'description' => '15-inch business laptop.',
                'purchase_price' => '120000.00',
                'sale_price' => '145000.00',
                'current_stock' => '12.000',
                'reorder_level' => '5.000',
                'is_active' => true,
            ],
            [
                'category_id' => $category('electronics'),
                'unit_id' => $unit('PCS'),
                'name' => 'Wireless Keyboard',
                'sku' => 'ELEC-KEY-002',
                'barcode' => '8901000000024',
                'description' => 'Compact wireless keyboard.',
                'purchase_price' => '4500.00',
                'sale_price' => '6500.00',
                'current_stock' => '3.000',
                'reorder_level' => '5.000',
                'is_active' => true,
            ],
            [
                'category_id' => $category('stationery'),
                'unit_id' => $unit('PCS'),
                'name' => 'A4 Notebook',
                'sku' => 'STAT-NOT-003',
                'barcode' => '8901000000031',
                'description' => '200-page ruled notebook.',
                'purchase_price' => '350.00',
                'sale_price' => '550.00',
                'current_stock' => '0.000',
                'reorder_level' => '10.000',
                'is_active' => true,
            ],
            [
                'category_id' => $category('groceries'),
                'unit_id' => $unit('KG'),
                'name' => 'Basmati Rice',
                'sku' => 'GROC-RIC-004',
                'barcode' => '8901000000048',
                'description' => 'Premium basmati rice.',
                'purchase_price' => '180.00',
                'sale_price' => '230.00',
                'current_stock' => '45.000',
                'reorder_level' => '20.000',
                'is_active' => true,
            ],
            [
                'category_id' => $category('furniture'),
                'unit_id' => $unit('PCS'),
                'name' => 'Office Chair',
                'sku' => 'FURN-CHR-005',
                'barcode' => '8901000000055',
                'description' => 'Ergonomic office chair.',
                'purchase_price' => '18000.00',
                'sale_price' => '24500.00',
                'current_stock' => '8.000',
                'reorder_level' => '4.000',
                'is_active' => true,
            ],
        ];
    }
}
