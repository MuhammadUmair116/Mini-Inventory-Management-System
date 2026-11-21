<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_purchases(): void
    {
        $this->get(route('purchases.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_cannot_access_purchase_endpoints(): void
    {
        $staff = User::factory()->create();
        $purchase = Purchase::factory()->create();

        $this->actingAs($staff)->get(route('purchases.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('purchases.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('purchases.show', $purchase))->assertForbidden();
        $this->actingAs($staff)->post(route('purchases.store'), $this->validPurchaseData())->assertForbidden();
    }

    public function test_admin_can_view_filtered_purchases_with_stats_and_options(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validPurchaseData([
            'invoice_number' => 'PUR-FILTER-001',
        ]);
        $this->recordPurchase($data, $admin);

        $response = $this->actingAs($admin)->get(route('purchases.index', [
            'search' => 'PUR-FILTER',
            'supplier_id' => $data['supplier_id'],
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Purchases/Index')
                ->where('filters.search', 'PUR-FILTER')
                ->has('purchases.data', 1)
                ->where('purchases.data.0.invoice_number', 'PUR-FILTER-001')
                ->where('purchases.data.0.items_count', 2)
                ->where('stats.total', 1)
                ->where('stats.items', 2)
                ->has('supplierOptions', 1)
                ->etc()
            );
    }

    public function test_admin_can_open_the_purchase_form_with_active_options(): void
    {
        Supplier::factory()->create();
        Supplier::factory()->inactive()->create();
        Product::factory()->create(['is_active' => true]);
        Product::factory()->create(['is_active' => false]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('purchases.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Purchases/Create')
                ->has('supplierOptions', 1)
                ->has('productOptions', 1)
                ->etc()
            );
    }

    public function test_admin_can_record_a_purchase_and_stock_is_updated_once(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validPurchaseData([
            'invoice_number' => 'pur-http-100',
            'total_amount' => '999999.99',
            'items' => [
                [
                    'product_id' => Product::factory()->create(['current_stock' => '10.000'])->id,
                    'quantity' => '2.000',
                    'unit_price' => '100.00',
                    'line_total' => '1.00',
                ],
                [
                    'product_id' => Product::factory()->create(['current_stock' => '5.000'])->id,
                    'quantity' => '3.000',
                    'unit_price' => '50.00',
                    'line_total' => '1.00',
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('purchases.store'), $data);

        $purchase = Purchase::query()->where('invoice_number', 'PUR-HTTP-100')->firstOrFail();

        $response->assertRedirect(route('purchases.show', $purchase))
            ->assertSessionHas('success', 'Purchase recorded successfully.');

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase->id,
            'invoice_number' => 'PUR-HTTP-100',
            'total_amount' => 350.00,
            'created_by' => $admin->id,
        ]);
        $this->assertDatabaseCount('purchase_items', 2);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'product_id' => $data['items'][0]['product_id'],
            'quantity' => 2.000,
            'unit_price' => 100.00,
            'line_total' => 200.00,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $data['items'][0]['product_id'],
            'current_stock' => 12.000,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $data['items'][1]['product_id'],
            'current_stock' => 8.000,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $data['items'][0]['product_id'],
            'type' => 'purchase',
            'quantity' => 2.000,
            'balance_after' => 12.000,
            'reference_id' => $purchase->id,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('purchases.show', $purchase))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Purchases/Show')
                ->where('purchase.invoice_number', 'PUR-HTTP-100')
                ->where('purchase.total_amount', '350.00')
                ->has('purchase.items', 2)
                ->has('purchase.stock_movements', 2)
                ->etc()
            );
    }

    public function test_duplicate_product_lines_are_rejected(): void
    {
        $data = $this->validPurchaseData();
        $data['items'][1]['product_id'] = $data['items'][0]['product_id'];
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('purchases.store'), $data)
            ->assertSessionHasErrors(['items.1.product_id']);

        $this->assertDatabaseMissing('purchases', ['invoice_number' => $data['invoice_number']]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_inactive_supplier_and_products_are_rejected(): void
    {
        $inactiveSupplier = Supplier::factory()->inactive()->create();
        $inactiveProduct = Product::factory()->inactive()->create();
        $data = $this->validPurchaseData([
            'supplier_id' => $inactiveSupplier->id,
        ]);
        $data['items'][1]['product_id'] = $inactiveProduct->id;
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('purchases.store'), $data)
            ->assertSessionHasErrors(['supplier_id', 'items.1.product_id']);
    }

    public function test_purchase_date_cannot_be_in_the_future(): void
    {
        $data = $this->validPurchaseData([
            'purchase_date' => now()->addDay()->toDateString(),
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('purchases.store'), $data)
            ->assertSessionHasErrors(['purchase_date']);
    }

    public function test_invoice_number_must_be_unique(): void
    {
        Purchase::factory()->create(['invoice_number' => 'PUR-DUPLICATE-001']);
        $data = $this->validPurchaseData(['invoice_number' => 'pur-duplicate-001']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('purchases.store'), $data)
            ->assertSessionHasErrors(['invoice_number']);
    }

    public function test_purchase_policy_allows_admin_only(): void
    {
        $purchase = Purchase::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Purchase::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Purchase::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $purchase));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Purchase::class));
        $this->assertFalse(Gate::forUser($staff)->allows('create', Purchase::class));
        $this->assertFalse(Gate::forUser($staff)->allows('view', $purchase));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function recordPurchase(array $data, User $user): Purchase
    {
        return app(PurchaseService::class)->create($data, $user);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPurchaseData(array $overrides = []): array
    {
        return array_merge([
            'supplier_id' => Supplier::factory()->create()->id,
            'invoice_number' => 'PUR-TEST-001',
            'purchase_date' => now()->toDateString(),
            'notes' => 'Test purchase.',
            'items' => [
                [
                    'product_id' => Product::factory()->create(['current_stock' => '10.000'])->id,
                    'quantity' => '2.000',
                    'unit_price' => '100.00',
                ],
                [
                    'product_id' => Product::factory()->create(['current_stock' => '5.000'])->id,
                    'quantity' => '3.000',
                    'unit_price' => '50.00',
                ],
            ],
        ], $overrides);
    }
}
