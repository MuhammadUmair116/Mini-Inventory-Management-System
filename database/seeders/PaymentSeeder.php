<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(private PaymentService $payments) {}

    /**
     * Seed demo receipts through the same service used by the application.
     */
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@inventory.test')->firstOrFail();

        foreach ($this->paymentData() as $payment) {
            if (Payment::query()->where('receipt_number', $payment['receipt_number'])->exists()) {
                continue;
            }

            $this->payments->create($payment, $admin);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentData(): array
    {
        $sale = fn (string $invoiceNumber): int => Sale::query()
            ->where('invoice_number', $invoiceNumber)
            ->firstOrFail()
            ->id;

        return [
            [
                'sale_id' => $sale('SAL-2026-0001'),
                'receipt_number' => 'REC-2026-0001',
                'payment_date' => '2026-11-13',
                'amount' => '100000.00',
                'method' => 'bank_transfer',
                'reference' => 'BANK-TRX-2201',
                'notes' => 'First instalment for the opening retail order.',
            ],
            [
                'sale_id' => $sale('SAL-2026-0002'),
                'receipt_number' => 'REC-2026-0002',
                'payment_date' => '2026-11-16',
                'amount' => '28550.00',
                'method' => 'cash',
                'reference' => null,
                'notes' => 'Full settlement of the stationery order.',
            ],
        ];
    }
}
