<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the default units of measure.
     */
    public function run(): void
    {
        foreach ($this->units() as $unit) {
            Unit::query()->updateOrCreate(
                ['short_name' => $unit['short_name']],
                [
                    'name' => $unit['name'],
                    'description' => $unit['description'],
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array{name: string, short_name: string, description: string}>
     */
    private function units(): array
    {
        return [
            ['name' => 'Piece', 'short_name' => 'PCS', 'description' => 'A single countable item.'],
            ['name' => 'Kilogram', 'short_name' => 'KG', 'description' => 'Metric unit of mass.'],
            ['name' => 'Liter', 'short_name' => 'LTR', 'description' => 'Metric unit of volume.'],
            ['name' => 'Meter', 'short_name' => 'MTR', 'description' => 'Metric unit of length.'],
            ['name' => 'Box', 'short_name' => 'BOX', 'description' => 'A packaged box of items.'],
        ];
    }
}
