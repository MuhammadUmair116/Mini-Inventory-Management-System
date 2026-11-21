<?php

namespace App\Services;

use App\Exceptions\OverpaymentException;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    /**
     * Record a payment against the locked sale and reject overpayment atomically.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Payment
    {
        return DB::transaction(function () use ($data, $user): Payment {
            $sale = Sale::query()
                ->whereKey($data['sale_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $paid = BigDecimal::of((string) (Payment::query()->where('sale_id', $sale->getKey())->sum('amount') ?? '0'));
            $due = BigDecimal::of((string) $sale->total_amount)
                ->minus($paid)
                ->toScale(2, RoundingMode::HalfUp);
            $amount = BigDecimal::of((string) $data['amount'])->toScale(2, RoundingMode::HalfUp);

            if ($amount->isLessThanOrEqualTo(0)) {
                throw new InvalidArgumentException('Payment amount must be greater than zero.');
            }

            if ($amount->isGreaterThan($due)) {
                throw new OverpaymentException(
                    "Payment of {$amount->toString()} exceeds the outstanding balance of {$due->toString()} for {$sale->invoice_number}."
                );
            }

            $payment = Payment::query()->create([
                'sale_id' => $sale->getKey(),
                'receipt_number' => $data['receipt_number'],
                'payment_date' => $data['payment_date'],
                'amount' => $amount->toString(),
                'method' => $data['method'],
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->getKey(),
            ]);

            return $payment->load(['sale.customer:id,name', 'createdBy:id,name']);
        }, attempts: 5);
    }
}
