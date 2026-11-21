<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('inventory.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_inactive_users_cannot_view_inventory(): void
    {
        $inactiveUser = User::factory()->inactive()->create();

        $response = $this->actingAs($inactiveUser)->get(route('inventory.index'));

        $response->assertForbidden();
    }

    public function test_staff_can_view_inventory_without_purchase_navigation(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('inventory.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Index')
                ->has('stats')
                ->has('products')
                ->has('movements')
                ->has('ledger')
                ->has('filters')
                ->has('categoryOptions')
                ->has('productOptions')
                ->has('movementTypeOptions', 4)
                ->where('permissions.viewPurchases', false)
                ->etc()
            );
    }

    public function test_admin_can_view_inventory_with_purchase_navigation(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('inventory.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Index')
                ->where('permissions.viewPurchases', true)
                ->etc()
            );
    }

    public function test_view_inventory_gate_allows_active_users_with_a_workspace_role(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $inactiveUser = User::factory()->inactive()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view-inventory'));
        $this->assertTrue(Gate::forUser($staff)->allows('view-inventory'));
        $this->assertFalse(Gate::forUser($inactiveUser)->allows('view-inventory'));
    }

    public function test_stock_stats_and_rows_reflect_active_products_at_purchase_price(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->inactive()->create([
            'current_stock' => '100.000',
            'purchase_price' => '9.00',
            'reorder_level' => '5.000',
        ]);
        Product::factory()->create([
            'name' => 'Inv In Stock',
            'current_stock' => '10.000',
            'reorder_level' => '5.000',
            'purchase_price' => '2.00',
        ]);
        Product::factory()->create([
            'name' => 'Inv Low Stock',
            'current_stock' => '5.000',
            'reorder_level' => '10.000',
            'purchase_price' => '3.00',
        ]);
        Product::factory()->create([
            'name' => 'Inv Out Of Stock',
            'current_stock' => '0.000',
            'reorder_level' => '5.000',
            'purchase_price' => '4.00',
        ]);

        $response = $this->actingAs($admin)->get(route('inventory.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Index')
                ->where('stats.active_products', 3)
                ->where('stats.in_stock', 1)
                ->where('stats.low_stock', 1)
                ->where('stats.out_of_stock', 1)
                ->where('stats.stock_value', '35.00')
                ->has('stats.low_stock_products', 1)
                ->where('stats.low_stock_products.0.name', 'Inv Low Stock')
                ->where('products.total', 4)
                ->where('products.data.0.name', 'Inv Out Of Stock')
                ->where('products.data.0.current_stock', '0.000')
                ->where('products.data.0.reorder_level', '5.000')
                ->where('products.data.0.stock_value', '0.00')
                ->where('products.data.0.stock_status', 'out_of_stock')
                ->where('products.data.0.is_active', true)
                ->etc()
            );
    }

    public function test_product_search_filters_stock_rows_by_name_or_sku(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['name' => 'Alpha Widget', 'sku' => 'ALPHA-1']);
        Product::factory()->create(['name' => 'Beta Widget', 'sku' => 'BETA-2']);

        $response = $this->actingAs($admin)->get(route('inventory.index', ['search' => 'ALPHA']));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Alpha Widget')
                ->where('filters.search', 'ALPHA')
                ->etc()
            );
    }

    public function test_category_filter_limits_stock_rows(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Filtered Category']);
        Product::factory()->create(['name' => 'Filtered Product', 'category_id' => $category->id]);
        Product::factory()->create(['name' => 'Other Product']);

        $response = $this->actingAs($admin)->get(route('inventory.index', [
            'category_id' => $category->id,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Filtered Product')
                ->where('products.data.0.category', 'Filtered Category')
                ->etc()
            );
    }

    public function test_stock_status_filter_limits_stock_rows(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->lowStock()->create(['name' => 'Low Item']);
        Product::factory()->create([
            'name' => 'Healthy Item',
            'current_stock' => '20.000',
            'reorder_level' => '5.000',
        ]);

        $response = $this->actingAs($admin)->get(route('inventory.index', [
            'stock_status' => 'low_stock',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Low Item')
                ->where('products.data.0.stock_status', 'low_stock')
                ->etc()
            );
    }

    public function test_active_state_filter_limits_stock_rows(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create(['name' => 'Active Item']);
        Product::factory()->inactive()->create(['name' => 'Inactive Item']);

        $response = $this->actingAs($admin)->get(route('inventory.index', ['is_active' => '0']));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Inactive Item')
                ->where('products.data.0.is_active', false)
                ->etc()
            );
    }

    public function test_movement_ledger_exposes_references_balances_and_totals(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Ledger Item', 'current_stock' => '8.000']);
        $purchase = Purchase::factory()->create([
            'invoice_number' => 'PUR-LEDGER-001',
            'created_by' => $admin->id,
        ]);
        $sale = Sale::factory()->create([
            'invoice_number' => 'SAL-LEDGER-001',
            'created_by' => $admin->id,
        ]);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'quantity' => '10.000',
            'balance_after' => '10.000',
            'reference_type' => $purchase->getMorphClass(),
            'reference_id' => $purchase->id,
            'created_by' => $admin->id,
            'created_at' => '2026-11-10 09:00:00',
            'notes' => 'Opening receipt',
        ]);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_SALE,
            'quantity' => '2.000',
            'balance_after' => '8.000',
            'reference_type' => $sale->getMorphClass(),
            'reference_id' => $sale->id,
            'created_by' => $admin->id,
            'created_at' => '2026-11-12 10:00:00',
            'notes' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('inventory.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Inventory/Index')
                ->where('ledger.movement_count', 2)
                ->where('ledger.inbound_quantity', '10.000')
                ->where('ledger.outbound_quantity', '2.000')
                ->has('movements.data', 2)
                ->where('movements.data.0.type', StockMovement::TYPE_SALE)
                ->where('movements.data.0.quantity', '2.000')
                ->where('movements.data.0.balance_after', '8.000')
                ->where('movements.data.0.reference_kind', 'sale')
                ->where('movements.data.0.reference_label', 'SAL-LEDGER-001')
                ->where('movements.data.0.recorded_by', $admin->name)
                ->where('movements.data.1.reference_kind', 'purchase')
                ->where('movements.data.1.reference_label', 'PUR-LEDGER-001')
                ->where('movements.data.1.notes', 'Opening receipt')
                ->etc()
            );
    }

    public function test_movement_filters_by_type_product_and_date(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Filtered Movements']);
        $otherProduct = Product::factory()->create(['name' => 'Other Movements']);
        $purchase = Purchase::factory()->create();

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'reference_type' => $purchase->getMorphClass(),
            'reference_id' => $purchase->id,
            'created_at' => '2026-11-10 09:00:00',
        ]);
        StockMovement::factory()->create([
            'product_id' => $product->id,
            'type' => StockMovement::TYPE_SALE,
            'reference_type' => Sale::class,
            'reference_id' => Sale::factory()->create()->id,
            'created_at' => '2026-11-12 09:00:00',
        ]);
        StockMovement::factory()->create([
            'product_id' => $otherProduct->id,
            'type' => StockMovement::TYPE_PURCHASE,
            'reference_type' => Purchase::class,
            'reference_id' => Purchase::factory()->create()->id,
            'created_at' => '2026-11-10 09:00:00',
        ]);

        $response = $this->actingAs($admin)->get(route('inventory.index', [
            'movement_type' => StockMovement::TYPE_PURCHASE,
            'product_id' => $product->id,
            'from_date' => '2026-11-10',
            'to_date' => '2026-11-11',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('ledger.movement_count', 1)
                ->has('movements.data', 1)
                ->where('movements.data.0.product_name', 'Filtered Movements')
                ->where('movements.data.0.type', StockMovement::TYPE_PURCHASE)
                ->etc()
            );
    }

    public function test_movement_search_matches_product_name_or_notes(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['name' => 'Cycle Count Product']);

        StockMovement::factory()->create([
            'product_id' => $product->id,
            'notes' => 'Annual verification',
        ]);
        StockMovement::factory()->create([
            'notes' => 'Unrelated adjustment',
        ]);

        $response = $this->actingAs($admin)->get(route('inventory.index', [
            'movement_search' => 'Cycle',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('ledger.movement_count', 1)
                ->has('movements.data', 1)
                ->where('movements.data.0.product_name', 'Cycle Count Product')
                ->etc()
            );
    }

    public function test_invalid_filters_return_validation_errors(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('inventory.index', [
            'stock_status' => 'unknown',
            'category_id' => 999999,
            'from_date' => '2026-11-12',
            'to_date' => '2026-11-10',
        ]));

        $response->assertSessionHasErrors(['stock_status', 'category_id', 'to_date']);
    }

    public function test_stock_and_movement_tables_paginate_independently(): void
    {
        $admin = User::factory()->admin()->create();
        $products = Product::factory()->count(11)->create();

        foreach ($products as $product) {
            StockMovement::factory()->create([
                'product_id' => $product->id,
                'reference_type' => Purchase::class,
                'reference_id' => Purchase::factory()->create()->id,
            ]);
        }

        $response = $this->actingAs($admin)->get(route('inventory.index', [
            'products_page' => 2,
            'movements_page' => 2,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('products.current_page', 2)
                ->where('products.total', 11)
                ->has('products.data', 1)
                ->where('movements.current_page', 2)
                ->where('movements.total', 11)
                ->has('movements.data', 1)
                ->etc()
            );
    }
}
