<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the default product categories.
     */
    public function run(): void
    {
        foreach ($this->categories() as $category) {
            Category::query()->updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, description: string}>
     */
    private function categories(): array
    {
        return [
            ['name' => 'Electronics', 'description' => 'Electronic devices and accessories.'],
            ['name' => 'Groceries', 'description' => 'Food and everyday household supplies.'],
            ['name' => 'Stationery', 'description' => 'Office and school stationery items.'],
            ['name' => 'Furniture', 'description' => 'Office and home furniture.'],
        ];
    }
}
