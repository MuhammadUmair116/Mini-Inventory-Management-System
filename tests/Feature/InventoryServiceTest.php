<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_increments_locked_stock_and_records_a_balanced_movement(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000']);
        $purchase = Purchase::factory()->create();
        $user = User::factory()->admin()->create();

        $movement = DB::transaction(fn (): StockMovement => app(InventoryService::class)->recordPurchaseStock(
            product: $product,
            quantity: '2.500',
            referenceType: $purchase->getMorphClass(),
            referenceId: $purchase->id,
            user: $user,
            notes: 'Test receipt.',
        ));

        $this->assertSame('12.500', $product->fresh()->current_stock);
        $this->assertSame('12.500', $movement->balance_after);
        $this->assertSame('2.500', $movement->quantity);
        $this->assertSame(StockMovement::TYPE_PURCHASE, $movement->type);
        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'product_id' => $product->id,
            'reference_type' => $purchase->getMorphClass(),
            'reference_id' => $purchase->id,
            'created_by' => $user->id,
        ]);
    }

    public function test_it_decrements_locked_stock_and_records_a_balanced_sale_movement(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000']);
        $sale = Sale::factory()->create();
        $user = User::factory()->admin()->create();

        $movement = DB::transaction(fn (): StockMovement => app(InventoryService::class)->recordSaleStock(
            product: $product,
            quantity: '2.500',
            referenceType: $sale->getMorphClass(),
            referenceId: $sale->id,
            user: $user,
            notes: 'Test sale.',
        ));

        $this->assertSame('7.500', $product->fresh()->current_stock);
        $this->assertSame('7.500', $movement->balance_after);
        $this->assertSame('2.500', $movement->quantity);
        $this->assertSame(StockMovement::TYPE_SALE, $movement->type);
        $this->assertDatabaseHas('stock_movements', [
            'id' => $movement->id,
            'product_id' => $product->id,
            'reference_type' => $sale->getMorphClass(),
            'reference_id' => $sale->id,
            'created_by' => $user->id,
        ]);
    }

    public function test_it_rejects_a_sale_that_exceeds_available_stock(): void
    {
        $product = Product::factory()->create(['current_stock' => '1.000']);
        $sale = Sale::factory()->create();

        try {
            DB::transaction(fn () => app(InventoryService::class)->recordSaleStock(
                product: $product,
                quantity: '2.000',
                referenceType: $sale->getMorphClass(),
                referenceId: $sale->id,
            ));
            $this->fail('Insufficient stock should have been rejected.');
        } catch (InsufficientStockException $exception) {
            $this->assertStringContainsString($product->name, $exception->getMessage());
        }

        $this->assertSame('1.000', $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_it_rejects_a_duplicate_sale_stock_movement(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000']);
        $sale = Sale::factory()->create();
        $service = app(InventoryService::class);

        DB::transaction(fn () => $service->recordSaleStock(
            product: $product,
            quantity: '2.000',
            referenceType: $sale->getMorphClass(),
            referenceId: $sale->id,
        ));

        try {
            DB::transaction(fn () => $service->recordSaleStock(
                product: $product,
                quantity: '1.000',
                referenceType: $sale->getMorphClass(),
                referenceId: $sale->id,
            ));
            $this->fail('Duplicate sale movement should have been rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('A stock movement already exists for this sale and product.', $exception->getMessage());
        }

        $this->assertSame('8.000', $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_it_rejects_non_positive_sale_quantities(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000']);
        $sale = Sale::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sale quantity must be greater than zero.');

        DB::transaction(fn () => app(InventoryService::class)->recordSaleStock(
            product: $product,
            quantity: '0.000',
            referenceType: $sale->getMorphClass(),
            referenceId: $sale->id,
        ));
    }

    public function test_it_rejects_purchase_movements_outside_a_transaction(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000']);
        $purchase = Purchase::factory()->create();
        $databaseManager = app('db');

        DB::shouldReceive('transactionLevel')->once()->andReturn(0);

        try {
            app(InventoryService::class)->recordPurchaseStock(
                product: $product,
                quantity: '1.000',
                referenceType: $purchase->getMorphClass(),
                referenceId: $purchase->id,
            );
            $this->fail('Purchase movement outside a transaction should have been rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Stock movements must be recorded inside a database transaction.', $exception->getMessage());
        } finally {
            app()->instance('db', $databaseManager);
            DB::clearResolvedInstance('db');
        }
    }

    public function test_it_rejects_sale_movements_outside_a_transaction(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000']);
        $sale = Sale::factory()->create();
        $databaseManager = app('db');

        DB::shouldReceive('transactionLevel')->once()->andReturn(0);

        try {
            app(InventoryService::class)->recordSaleStock(
                product: $product,
                quantity: '1.000',
                referenceType: $sale->getMorphClass(),
                referenceId: $sale->id,
            );
            $this->fail('Sale movement outside a transaction should have been rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Stock movements must be recorded inside a database transaction.', $exception->getMessage());
        } finally {
            app()->instance('db', $databaseManager);
            DB::clearResolvedInstance('db');
        }
    }

    public function test_it_rejects_a_duplicate_purchase_stock_movement(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000']);
        $purchase = Purchase::factory()->create();
        $service = app(InventoryService::class);

        DB::transaction(fn () => $service->recordPurchaseStock(
            product: $product,
            quantity: '2.000',
            referenceType: $purchase->getMorphClass(),
            referenceId: $purchase->id,
        ));

        try {
            DB::transaction(fn () => $service->recordPurchaseStock(
                product: $product,
                quantity: '1.000',
                referenceType: $purchase->getMorphClass(),
                referenceId: $purchase->id,
            ));
            $this->fail('Duplicate purchase movement should have been rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('A stock movement already exists for this purchase and product.', $exception->getMessage());
        }

        $this->assertSame('12.000', $product->fresh()->current_stock);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_it_rejects_non_positive_purchase_quantities(): void
    {
        $product = Product::factory()->create(['current_stock' => '10.000']);
        $purchase = Purchase::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase quantity must be greater than zero.');

        DB::transaction(fn () => app(InventoryService::class)->recordPurchaseStock(
            product: $product,
            quantity: '0.000',
            referenceType: $purchase->getMorphClass(),
            referenceId: $purchase->id,
        ));
    }
}
