<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private PurchaseService $purchases) {}

    /**
     * Seed demo purchases through the same service used by the application.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@inventory.test')->firstOrFail();

        foreach ($this->purchases() as $purchase) {
            if (Purchase::query()->where('invoice_number', $purchase['invoice_number'])->exists()) {
                continue;
            }

            $this->purchases->create($purchase, $admin);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function purchases(): array
    {
        $supplier = fn (string $email): int => Supplier::query()->where('email', $email)->firstOrFail()->id;
        $product = fn (string $sku): int => Product::query()->where('sku', $sku)->firstOrFail()->id;

        return [
            [
                'supplier_id' => $supplier('procurement@techworld.test'),
                'invoice_number' => 'PUR-2026-0001',
                'purchase_date' => '2026-11-10',
                'notes' => 'Opening stock replenishment for electronics.',
                'items' => [
                    [
                        'product_id' => $product('ELEC-LAP-001'),
                        'quantity' => '5.000',
                        'unit_price' => '118000.00',
                    ],
                    [
                        'product_id' => $product('ELEC-KEY-002'),
                        'quantity' => '1.000',
                        'unit_price' => '4200.00',
                    ],
                ],
            ],
            [
                'supplier_id' => $supplier('orders@officehub.test'),
                'invoice_number' => 'PUR-2026-0002',
                'purchase_date' => '2026-11-14',
                'notes' => 'Restock for household and furniture lines.',
                'items' => [
                    [
                        'product_id' => $product('GROC-RIC-004'),
                        'quantity' => '20.000',
                        'unit_price' => '165.00',
                    ],
                    [
                        'product_id' => $product('FURN-CHR-005'),
                        'quantity' => '2.000',
                        'unit_price' => '17000.00',
                    ],
                ],
            ],
        ];
    }
}
