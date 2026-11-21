<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_sales(): void
    {
        $this->get(route('sales.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_view_sales_and_open_the_create_form(): void
    {
        Customer::factory()->create(['is_active' => true]);
        Customer::factory()->inactive()->create();
        Product::factory()->create(['is_active' => true]);
        Product::factory()->inactive()->create();
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('sales.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->where('can.create', true)
                ->has('customerOptions', 1)
                ->etc()
            );

        $this->actingAs($staff)
            ->get(route('sales.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Create')
                ->has('customerOptions', 1)
                ->has('productOptions', 1)
                ->etc()
            );
    }

    public function test_staff_can_record_a_sale_and_stock_is_decremented_once(): void
    {
        $staff = User::factory()->create();
        $data = $this->validSaleData([
            'invoice_number' => 'sal-http-100',
            'items' => [
                [
                    'product_id' => Product::factory()->create(['current_stock' => '10.000', 'sale_price' => '100.00'])->id,
                    'quantity' => '2.000',
                    'discount_percentage' => '0.00',
                    'unit_price' => '1.00',
                ],
                [
                    'product_id' => Product::factory()->create(['current_stock' => '5.000', 'sale_price' => '50.00'])->id,
                    'quantity' => '3.000',
                    'discount_percentage' => '0.00',
                ],
            ],
        ]);

        $response = $this->actingAs($staff)->post(route('sales.store'), $data);

        $sale = Sale::query()->where('invoice_number', 'SAL-HTTP-100')->firstOrFail();

        $response->assertRedirect(route('sales.show', $sale))
            ->assertSessionHas('success', 'Sale recorded successfully.');

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'invoice_number' => 'SAL-HTTP-100',
            'subtotal_amount' => 350.00,
            'discount_amount' => 0.00,
            'total_amount' => 350.00,
            'created_by' => $staff->id,
        ]);
        $this->assertDatabaseCount('sale_items', 2);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $data['items'][0]['product_id'],
            'quantity' => 2.000,
            'unit_price' => 100.00,
            'line_total' => 200.00,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $data['items'][0]['product_id'],
            'current_stock' => 8.000,
        ]);
        $this->assertDatabaseHas('products', [
            'id' => $data['items'][1]['product_id'],
            'current_stock' => 2.000,
        ]);
        $this->assertDatabaseCount('stock_movements', 2);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $data['items'][0]['product_id'],
            'type' => 'sale',
            'quantity' => 2.000,
            'balance_after' => 8.000,
            'reference_id' => $sale->id,
            'created_by' => $staff->id,
        ]);
    }

    public function test_sale_totals_use_catalogue_prices_and_apply_line_discounts(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validSaleData([
            'invoice_number' => 'SAL-DISCOUNT-001',
            'items' => [
                [
                    'product_id' => Product::factory()->create(['current_stock' => '10.000', 'sale_price' => '100.00'])->id,
                    'quantity' => '2.000',
                    'discount_percentage' => '5.00',
                    'unit_price' => '1.00',
                    'line_total' => '1.00',
                ],
                [
                    'product_id' => Product::factory()->create(['current_stock' => '5.000', 'sale_price' => '50.00'])->id,
                    'quantity' => '3.000',
                    'discount_percentage' => '0.00',
                ],
            ],
        ]);

        $this->actingAs($admin)->post(route('sales.store'), $data)
            ->assertRedirect(route('sales.show', Sale::query()->where('invoice_number', 'SAL-DISCOUNT-001')->firstOrFail()));

        $sale = Sale::query()->where('invoice_number', 'SAL-DISCOUNT-001')->firstOrFail();

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'subtotal_amount' => 350.00,
            'discount_amount' => 10.00,
            'total_amount' => 340.00,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $data['items'][0]['product_id'],
            'unit_price' => 100.00,
            'discount_percentage' => 5.00,
            'line_total' => 190.00,
        ]);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $data['items'][1]['product_id'],
            'unit_price' => 50.00,
            'discount_percentage' => 0.00,
            'line_total' => 150.00,
        ]);
        $this->assertDatabaseHas('products', ['id' => $data['items'][0]['product_id'], 'current_stock' => 8.000]);
        $this->assertDatabaseHas('products', ['id' => $data['items'][1]['product_id'], 'current_stock' => 2.000]);
    }

    public function test_admin_can_view_filtered_sales_with_stats_and_options(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validSaleData(['invoice_number' => 'SAL-FILTER-001']);
        app(SaleService::class)->create($data, $admin);

        $response = $this->actingAs($admin)->get(route('sales.index', [
            'search' => 'SAL-FILTER',
            'customer_id' => $data['customer_id'],
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->where('filters.search', 'SAL-FILTER')
                ->has('sales.data', 1)
                ->where('sales.data.0.invoice_number', 'SAL-FILTER-001')
                ->where('sales.data.0.items_count', 2)
                ->where('stats.total', 1)
                ->where('stats.items', 2)
                ->has('customerOptions', 1)
                ->where('can.create', true)
                ->etc()
            );
    }

    public function test_show_page_displays_the_sale_document_items_and_movements(): void
    {
        $admin = User::factory()->admin()->create();
        $data = $this->validSaleData(['invoice_number' => 'SAL-SHOW-001']);
        $sale = app(SaleService::class)->create($data, $admin);

        $this->actingAs($admin)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Show')
                ->where('sale.invoice_number', 'SAL-SHOW-001')
                ->where('sale.subtotal_amount', '350.00')
                ->where('sale.total_amount', '350.00')
                ->where('sale.customer.name', $sale->customer->name)
                ->has('sale.items', 2)
                ->has('sale.stock_movements', 2)
                ->etc()
            );
    }

    public function test_sale_index_includes_live_payment_balances_after_a_payment(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = app(SaleService::class)->create($this->validSaleData([
            'invoice_number' => 'SAL-PAY-INDEX-001',
        ]), $admin);
        app(PaymentService::class)->create([
            'sale_id' => $sale->id,
            'receipt_number' => 'PAY-SALE-INDEX-001',
            'payment_date' => now()->toDateString(),
            'amount' => '100.00',
            'method' => Payment::METHOD_CASH,
            'reference' => null,
            'notes' => null,
        ], $admin);

        $this->actingAs($admin)
            ->get(route('sales.index', ['search' => 'SAL-PAY-INDEX-001']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Index')
                ->has('sales.data', 1)
                ->where('sales.data.0.paid_amount', '100.00')
                ->where('sales.data.0.due_amount', '250.00')
                ->where('sales.data.0.payment_status', Sale::PAYMENT_STATUS_PARTIAL)
                ->where('stats.outstanding_amount', '250.00')
                ->etc()
            );
    }

    public function test_show_page_includes_payment_history_and_settlement_status(): void
    {
        $admin = User::factory()->admin()->create();
        $sale = app(SaleService::class)->create($this->validSaleData([
            'invoice_number' => 'SAL-PAY-SHOW-001',
        ]), $admin);
        app(PaymentService::class)->create([
            'sale_id' => $sale->id,
            'receipt_number' => 'PAY-SALE-SHOW-001',
            'payment_date' => now()->toDateString(),
            'amount' => '350.00',
            'method' => Payment::METHOD_CASH,
            'reference' => null,
            'notes' => null,
        ], $admin);

        $this->actingAs($admin)
            ->get(route('sales.show', $sale))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Sales/Show')
                ->where('sale.paid_amount', '350.00')
                ->where('sale.due_amount', '0.00')
                ->where('sale.payment_status', Sale::PAYMENT_STATUS_PAID)
                ->has('sale.payments', 1)
                ->where('sale.payments.0.receipt_number', 'PAY-SALE-SHOW-001')
                ->where('sale.payments.0.created_by.name', $admin->name)
                ->etc()
            );
    }

    public function test_duplicate_product_lines_are_rejected(): void
    {
        $data = $this->validSaleData();
        $data['items'][1]['product_id'] = $data['items'][0]['product_id'];
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('sales.store'), $data)
            ->assertSessionHasErrors(['items.1.product_id']);

        $this->assertDatabaseMissing('sales', ['invoice_number' => $data['invoice_number']]);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_inactive_customer_and_products_are_rejected(): void
    {
        $inactiveCustomer = Customer::factory()->inactive()->create();
        $inactiveProduct = Product::factory()->inactive()->create();
        $data = $this->validSaleData(['customer_id' => $inactiveCustomer->id]);
        $data['items'][1]['product_id'] = $inactiveProduct->id;
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('sales.store'), $data)
            ->assertSessionHasErrors(['customer_id', 'items.1.product_id']);
    }

    public function test_sale_date_cannot_be_in_the_future(): void
    {
        $data = $this->validSaleData([
            'sale_date' => now()->addDay()->toDateString(),
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('sales.store'), $data)
            ->assertSessionHasErrors(['sale_date']);
    }

    public function test_invoice_number_must_be_unique(): void
    {
        Sale::factory()->create(['invoice_number' => 'SAL-DUPLICATE-001']);
        $data = $this->validSaleData(['invoice_number' => 'sal-duplicate-001']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('sales.store'), $data)
            ->assertSessionHasErrors(['invoice_number']);
    }

    public function test_insufficient_stock_is_rejected_and_rolls_back_the_whole_sale(): void
    {
        $admin = User::factory()->admin()->create();
        $stockedProduct = Product::factory()->create(['current_stock' => '5.000', 'sale_price' => '100.00']);
        $shortProduct = Product::factory()->create(['current_stock' => '1.000', 'sale_price' => '50.00']);
        $data = $this->validSaleData([
            'invoice_number' => 'SAL-OUT-001',
            'items' => [
                [
                    'product_id' => $stockedProduct->id,
                    'quantity' => '1.000',
                    'discount_percentage' => '0.00',
                ],
                [
                    'product_id' => $shortProduct->id,
                    'quantity' => '2.000',
                    'discount_percentage' => '0.00',
                ],
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('sales.store'), $data);

        $response->assertRedirect(route('sales.create'))->assertSessionHas('error');
        $this->assertStringContainsString('Insufficient stock', session('error'));

        $this->assertDatabaseMissing('sales', ['invoice_number' => 'SAL-OUT-001']);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('5.000', $stockedProduct->fresh()->current_stock);
        $this->assertSame('1.000', $shortProduct->fresh()->current_stock);
    }

    public function test_sale_policy_allows_both_roles_to_view_and_create(): void
    {
        $sale = Sale::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Sale::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Sale::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $sale));
        $this->assertTrue(Gate::forUser($staff)->allows('viewAny', Sale::class));
        $this->assertTrue(Gate::forUser($staff)->allows('create', Sale::class));
        $this->assertTrue(Gate::forUser($staff)->allows('view', $sale));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $sale));
        $this->assertFalse(Gate::forUser($staff)->allows('delete', $sale));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validSaleData(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => Customer::factory()->create()->id,
            'invoice_number' => 'SAL-TEST-001',
            'sale_date' => now()->toDateString(),
            'notes' => 'Test sale.',
            'items' => [
                [
                    'product_id' => Product::factory()->create(['current_stock' => '10.000', 'sale_price' => '100.00'])->id,
                    'quantity' => '2.000',
                    'discount_percentage' => '0.00',
                ],
                [
                    'product_id' => Product::factory()->create(['current_stock' => '5.000', 'sale_price' => '50.00'])->id,
                    'quantity' => '3.000',
                    'discount_percentage' => '0.00',
                ],
            ],
        ], $overrides);
    }
}
