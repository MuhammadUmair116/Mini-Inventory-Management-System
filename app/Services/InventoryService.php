<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class InventoryService
{
    /**
     * Record an inbound purchase movement and update the locked product's stock.
     *
     * The caller must open the database transaction so the purchase document,
     * its line items, and this stock mutation commit or roll back together.
     *
     * @param  class-string  $referenceType
     */
    public function recordPurchaseStock(
        Product $product,
        string $quantity,
        string $referenceType,
        int $referenceId,
        ?User $user = null,
        ?string $notes = null,
    ): StockMovement {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('Stock movements must be recorded inside a database transaction.');
        }

        $lockedProduct = Product::query()
            ->whereKey($product->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $existingMovement = StockMovement::query()
            ->where('type', StockMovement::TYPE_PURCHASE)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('product_id', $lockedProduct->getKey())
            ->first();

        if ($existingMovement !== null) {
            throw new RuntimeException('A stock movement already exists for this purchase and product.');
        }

        $receivedQuantity = BigDecimal::of($quantity)->toScale(3, RoundingMode::HalfUp);

        if ($receivedQuantity->isLessThanOrEqualTo(0)) {
            throw new InvalidArgumentException('Purchase quantity must be greater than zero.');
        }

        $balanceAfter = BigDecimal::of((string) $lockedProduct->current_stock)
            ->plus($receivedQuantity)
            ->toScale(3);

        $lockedProduct->forceFill([
            'current_stock' => $balanceAfter->toString(),
        ])->save();

        return StockMovement::query()->create([
            'product_id' => $lockedProduct->getKey(),
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity' => $receivedQuantity->toString(),
            'balance_after' => $balanceAfter->toString(),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $user?->getKey(),
        ]);
    }

    /**
     * Record an outbound sale movement and decrement the locked product's stock.
     *
     * The caller must open the database transaction so the sale document,
     * its line items, and this stock mutation commit or roll back together.
     *
     * @param  class-string  $referenceType
     */
    public function recordSaleStock(
        Product $product,
        string $quantity,
        string $referenceType,
        int $referenceId,
        ?User $user = null,
        ?string $notes = null,
    ): StockMovement {
        if (DB::transactionLevel() === 0) {
            throw new RuntimeException('Stock movements must be recorded inside a database transaction.');
        }

        $lockedProduct = Product::query()
            ->whereKey($product->getKey())
            ->lockForUpdate()
            ->firstOrFail();

        $existingMovement = StockMovement::query()
            ->where('type', StockMovement::TYPE_SALE)
            ->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->where('product_id', $lockedProduct->getKey())
            ->first();

        if ($existingMovement !== null) {
            throw new RuntimeException('A stock movement already exists for this sale and product.');
        }

        $soldQuantity = BigDecimal::of($quantity)->toScale(3, RoundingMode::HalfUp);

        if ($soldQuantity->isLessThanOrEqualTo(0)) {
            throw new InvalidArgumentException('Sale quantity must be greater than zero.');
        }

        $availableStock = BigDecimal::of((string) $lockedProduct->current_stock)->toScale(3);

        if ($availableStock->isLessThan($soldQuantity)) {
            throw new InsufficientStockException(
                "Insufficient stock for {$lockedProduct->name}. Available: {$availableStock->toString()}."
            );
        }

        $balanceAfter = $availableStock->minus($soldQuantity)->toScale(3);

        $lockedProduct->forceFill([
            'current_stock' => $balanceAfter->toString(),
        ])->save();

        return StockMovement::query()->create([
            'product_id' => $lockedProduct->getKey(),
            'type' => StockMovement::TYPE_SALE,
            'quantity' => $soldQuantity->toString(),
            'balance_after' => $balanceAfter->toString(),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $user?->getKey(),
        ]);
    }
}
