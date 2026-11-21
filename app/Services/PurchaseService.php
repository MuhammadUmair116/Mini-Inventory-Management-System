<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PurchaseService
{
    public function __construct(private InventoryService $inventory) {}

    /**
     * Record a purchase, its line items, and the matching stock movements atomically.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): Purchase
    {
        Supplier::query()->whereKey($data['supplier_id'])->active()->firstOrFail();

        return DB::transaction(function () use ($data, $user): Purchase {
            $totalAmount = BigDecimal::zero();

            $purchase = Purchase::query()->create([
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'],
                'purchase_date' => $data['purchase_date'],
                'notes' => $data['notes'] ?? null,
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
                $unitPrice = BigDecimal::of((string) $item['unit_price'])->toScale(2, RoundingMode::HalfUp);

                if ($unitPrice->isLessThan(0)) {
                    throw new InvalidArgumentException('Purchase unit price cannot be negative.');
                }

                $lineTotal = $quantity->multipliedBy($unitPrice)->toScale(2, RoundingMode::HalfUp);
                $totalAmount = $totalAmount->plus($lineTotal)->toScale(2);

                PurchaseItem::query()->create([
                    'purchase_id' => $purchase->getKey(),
                    'product_id' => $product->getKey(),
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'unit_short_name' => $product->unit?->short_name,
                    'quantity' => $quantity->toString(),
                    'unit_price' => $unitPrice->toString(),
                    'line_total' => $lineTotal->toString(),
                ]);

                $this->inventory->recordPurchaseStock(
                    product: $product,
                    quantity: $quantity->toString(),
                    referenceType: $purchase->getMorphClass(),
                    referenceId: $purchase->getKey(),
                    user: $user,
                    notes: "Received {$product->name} for {$purchase->invoice_number}.",
                );
            }

            $purchase->forceFill([
                'total_amount' => $totalAmount->toString(),
            ])->save();

            return $purchase->load(['supplier:id,name', 'createdBy:id,name', 'items.product']);
        }, attempts: 5);
    }
}
