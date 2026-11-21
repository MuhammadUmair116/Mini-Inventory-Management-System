<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private SaleService $sales) {}

    /**
     * Seed demo sales through the same service used by the application.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@inventory.test')->firstOrFail();

        foreach ($this->saleData() as $sale) {
            if (Sale::query()->where('invoice_number', $sale['invoice_number'])->exists()) {
                continue;
            }

            $this->sales->create($sale, $admin);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function saleData(): array
    {
        $customer = fn (string $email): int => Customer::query()->where('email', $email)->firstOrFail()->id;
        $product = fn (string $sku): int => Product::query()->where('sku', $sku)->firstOrFail()->id;

        return [
            [
                'customer_id' => $customer('procurement@greenvalley.test'),
                'invoice_number' => 'SAL-2026-0001',
                'sale_date' => '2026-11-12',
                'notes' => 'Opening retail order for electronics and staples.',
                'items' => [
                    [
                        'product_id' => $product('ELEC-LAP-001'),
                        'quantity' => '2.000',
                        'discount_percentage' => '5.00',
                    ],
                    [
                        'product_id' => $product('GROC-RIC-004'),
                        'quantity' => '10.000',
                        'discount_percentage' => '0.00',
                    ],
                ],
            ],
            [
                'customer_id' => $customer('orders@metrostationery.test'),
                'invoice_number' => 'SAL-2026-0002',
                'sale_date' => '2026-11-15',
                'notes' => 'Furniture and keyboard restock order.',
                'items' => [
                    [
                        'product_id' => $product('FURN-CHR-005'),
                        'quantity' => '1.000',
                        'discount_percentage' => '10.00',
                    ],
                    [
                        'product_id' => $product('ELEC-KEY-002'),
                        'quantity' => '1.000',
                        'discount_percentage' => '0.00',
                    ],
                ],
            ],
        ];
    }
}
