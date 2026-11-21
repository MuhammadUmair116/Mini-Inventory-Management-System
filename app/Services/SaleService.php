<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SaleService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Record a sale, its line items, and the matching stock movements atomically.
     *
     * Prices always come from the product catalogue; client-supplied prices are ignored.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Sale
    {
        Customer::query()->whereKey($data['customer_id'])->active()->firstOrFail();

        return DB::transaction(function () use ($data, $user): Sale {
            $subtotal = BigDecimal::zero();
            $discountTotal = BigDecimal::zero();
            $total = BigDecimal::zero();

            $sale = Sale::query()->create([
                'customer_id' => $data['customer_id'],
                'invoice_number' => $data['invoice_number'],
                'sale_date' => $data['sale_date'],
                'status' => Sale::STATUS_COMPLETED,
                'notes' => $data['notes'] ?? null,
                'subtotal_amount' => '0.00',
                'discount_amount' => '0.00',
                'total_amount' => '0.00',
                'created_by' => $user->getKey(),
            ]);

            foreach ($data['items'] as $item) {
                $product = Product::query()
                    ->whereKey($item['product_id'])
                    ->active()
                    ->with('unit:id,short_name')
                    ->firstOrFail();

                $quantity = BigDecimal::of((string) $item['quantity'])->toScale(3, RoundingMode::HalfUp);
                $unitPrice = BigDecimal::of((string) $product->sale_price)->toScale(2, RoundingMode::HalfUp);
                $discountPercentage = BigDecimal::of((string) ($item['discount_percentage'] ?? '0'))->toScale(2, RoundingMode::HalfUp);

                if ($quantity->isLessThanOrEqualTo(0)) {
                    throw new InvalidArgumentException('Sale quantity must be greater than zero.');
                }

                if ($discountPercentage->isLessThan(0) || $discountPercentage->isGreaterThan(100)) {
                    throw new InvalidArgumentException('Discount percentage must be between 0 and 100.');
                }

                $grossLineTotal = $quantity->multipliedBy($unitPrice)->toScale(2, RoundingMode::HalfUp);
                $lineDiscount = $grossLineTotal
                    ->multipliedBy($discountPercentage->dividedBy('100', 4, RoundingMode::HalfUp))
                    ->toScale(2, RoundingMode::HalfUp);
                $lineTotal = $grossLineTotal->minus($lineDiscount)->toScale(2, RoundingMode::HalfUp);

                $subtotal = $subtotal->plus($grossLineTotal)->toScale(2);
                $discountTotal = $discountTotal->plus($lineDiscount)->toScale(2);
                $total = $total->plus($lineTotal)->toScale(2);

                SaleItem::query()->create([
                    'sale_id' => $sale->getKey(),
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_short_name' => $product->unit?->short_name,
                    'quantity' => $quantity->toString(),
                    'unit_price' => $unitPrice->toString(),
                    'discount_percentage' => $discountPercentage->toString(),
                    'line_total' => $lineTotal->toString(),
                ]);

                $this->inventory->recordSaleStock(
                    product: $product,
                    quantity: $quantity->toString(),
                    referenceType: $sale->getMorphClass(),
                    referenceId: $sale->getKey(),
                    user: $user,
                    notes: "Sold {$product->name} on {$sale->invoice_number}.",
                );
            }

            $sale->forceFill([
                'subtotal_amount' => $subtotal->toString(),
                'discount_amount' => $discountTotal->toString(),
                'total_amount' => $total->toString(),
            ])->save();

            return $sale->load(['customer:id,name', 'createdBy:id,name', 'items.product:id,name,sku']);
        }, attempts: 5);
    }
}
