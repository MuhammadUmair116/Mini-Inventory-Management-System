<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-17 12:00:00');
    }

    public function test_guests_are_redirected_to_login_when_viewing_reports(): void
    {
        $this->get(route('reports.sales'))
            ->assertRedirect(route('login'));
    }

    public function test_inactive_users_cannot_view_reports(): void
    {
        $inactiveUser = User::factory()->inactive()->create();

        $this->actingAs($inactiveUser)
            ->get(route('reports.sales'))
            ->assertForbidden();
    }

    public function test_staff_can_view_the_sales_report(): void
    {
        $staff = User::factory()->create();
        $this->seedReportData();

        $this->actingAs($staff)
            ->get(route('reports.sales', [
                'from_date' => '2026-01-01',
                'to_date' => '2026-11-17',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Sales')
                ->where('filters.from_date', '2026-01-01')
                ->where('filters.to_date', '2026-11-17')
                ->etc()
            );
    }

    public function test_admin_can_view_the_sales_report(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('reports.sales'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Sales')
                ->etc()
            );
    }

    public function test_report_defaults_to_the_current_year_through_today(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('reports.sales'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Sales')
                ->where('filters.from_date', '2026-01-01')
                ->where('filters.to_date', '2026-11-17')
                ->etc()
            );
    }

    public function test_report_aggregates_sales_discounts_payments_and_outstanding_for_the_selected_range(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('reports.sales', [
                'from_date' => '2026-01-01',
                'to_date' => '2026-11-17',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Reports/Sales')
                ->where('stats.sales_total', '1500.00')
                ->where('stats.discount_total', '50.00')
                ->where('stats.payments_total', '500.00')
                ->where('stats.outstanding_total', '1800.00')
                ->where('stats.sales_count', 2)
                ->where('stats.payment_count', 2)
                ->where('stats.average_sale', '750.00')
                ->etc()
            );
    }

    public function test_report_builds_a_monthly_sales_and_payment_trend(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('reports.sales', [
                'from_date' => '2026-09-01',
                'to_date' => '2026-11-17',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('sales_trend', 3)
                ->where('sales_trend.0.period', '2026-09')
                ->where('sales_trend.0.sales', '0.00')
                ->where('sales_trend.1.period', '2026-10')
                ->where('sales_trend.1.sales', '500.00')
                ->where('sales_trend.1.payments', '200.00')
                ->where('sales_trend.1.sales_count', 1)
                ->where('sales_trend.2.period', '2026-11')
                ->where('sales_trend.2.sales', '1000.00')
                ->where('sales_trend.2.payments', '300.00')
                ->etc()
            );
    }

    public function test_report_ranks_top_products_and_customers_by_revenue(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('reports.sales', [
                'from_date' => '2026-01-01',
                'to_date' => '2026-11-17',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('top_products', 2)
                ->where('top_products.0.sku', 'REP-001')
                ->where('top_products.0.quantity', '8.000')
                ->where('top_products.0.revenue', '1200.00')
                ->where('top_products.1.sku', 'REP-002')
                ->where('top_products.1.revenue', '300.00')
                ->has('top_customers', 2)
                ->where('top_customers.0.name', 'Alpha Traders')
                ->where('top_customers.0.sales_count', 1)
                ->where('top_customers.0.revenue', '1000.00')
                ->where('top_customers.1.name', 'Beta Stores')
                ->where('top_customers.1.revenue', '500.00')
                ->etc()
            );
    }

    public function test_report_summarizes_payments_by_method(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedReportData();

        $this->actingAs($admin)
            ->get(route('reports.sales', [
                'from_date' => '2026-01-01',
                'to_date' => '2026-11-17',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('payment_methods', 4)
                ->where('payment_methods.0.method', Payment::METHOD_CASH)
                ->where('payment_methods.0.payment_count', 1)
                ->where('payment_methods.0.total', '300.00')
                ->where('payment_methods.1.method', Payment::METHOD_BANK_TRANSFER)
                ->where('payment_methods.1.total', '200.00')
                ->where('payment_methods.2.method', Payment::METHOD_CARD)
                ->where('payment_methods.2.payment_count', 0)
                ->where('payment_methods.2.total', '0.00')
                ->etc()
            );
    }

    public function test_report_includes_a_current_inventory_snapshot(): void
    {
        $admin = User::factory()->admin()->create();
        $this->seedReportData();
        Product::factory()->inactive()->create(['current_stock' => '99.000']);

        $this->actingAs($admin)
            ->get(route('reports.sales'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('inventory.active_products', 3)
                ->where('inventory.in_stock', 1)
                ->where('inventory.low_stock', 1)
                ->where('inventory.out_of_stock', 1)
                ->where('inventory.stock_value', '1100.00')
                ->has('inventory.low_stock_products', 1)
                ->where('inventory.low_stock_products.0.sku', 'REP-002')
                ->where('inventory.low_stock_products.0.current_stock', '2.000')
                ->where('inventory.low_stock_products.0.reorder_level', '5.000')
                ->etc()
            );
    }

    public function test_report_rejects_invalid_date_filters(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('reports.sales', [
                'from_date' => '2026-11-10',
                'to_date' => '2026-11-01',
            ]))
            ->assertSessionHasErrors(['to_date']);

        $this->actingAs($admin)
            ->get(route('reports.sales', ['from_date' => '2026-11-18']))
            ->assertSessionHasErrors(['from_date']);

        $this->actingAs($admin)
            ->get(route('reports.sales', ['from_date' => '17-11-2026']))
            ->assertSessionHasErrors(['from_date']);
    }

    public function test_view_reports_gate_allows_active_admin_and_staff_only(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $inactiveUser = User::factory()->inactive()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view-reports'));
        $this->assertTrue(Gate::forUser($staff)->allows('view-reports'));
        $this->assertFalse(Gate::forUser($inactiveUser)->allows('view-reports'));
    }

    /**
     * Create a fixed dataset with sales and payments inside and outside the report range.
     *
     * @return array<string, mixed>
     */
    private function seedReportData(): array
    {
        $admin = User::factory()->admin()->create();
        $alpha = Customer::factory()->create(['name' => 'Alpha Traders']);
        $beta = Customer::factory()->create(['name' => 'Beta Stores']);
        $oldCustomer = Customer::factory()->create(['name' => 'Old Customer']);

        $firstProduct = Product::factory()->create([
            'name' => 'Report Product One',
            'sku' => 'REP-001',
            'purchase_price' => '100.00',
            'sale_price' => '150.00',
            'current_stock' => '10.000',
            'reorder_level' => '5.000',
            'is_active' => true,
        ]);
        $secondProduct = Product::factory()->create([
            'name' => 'Report Product Two',
            'sku' => 'REP-002',
            'purchase_price' => '50.00',
            'sale_price' => '100.00',
            'current_stock' => '2.000',
            'reorder_level' => '5.000',
            'is_active' => true,
        ]);
        $emptyProduct = Product::factory()->create([
            'name' => 'Report Product Three',
            'sku' => 'REP-003',
            'purchase_price' => '25.00',
            'sale_price' => '40.00',
            'current_stock' => '0.000',
            'reorder_level' => '5.000',
            'is_active' => true,
        ]);

        $novemberSale = Sale::factory()->create([
            'customer_id' => $alpha->id,
            'invoice_number' => 'SAL-REP-001',
            'sale_date' => '2026-11-10',
            'subtotal_amount' => '1050.00',
            'discount_amount' => '50.00',
            'total_amount' => '1000.00',
            'created_by' => $admin->id,
        ]);
        SaleItem::factory()->create([
            'sale_id' => $novemberSale->id,
            'product_id' => $firstProduct->id,
            'product_name' => $firstProduct->name,
            'sku' => $firstProduct->sku,
            'quantity' => '6.000',
            'unit_price' => '150.00',
            'discount_percentage' => '0.00',
            'line_total' => '900.00',
        ]);
        SaleItem::factory()->create([
            'sale_id' => $novemberSale->id,
            'product_id' => $secondProduct->id,
            'product_name' => $secondProduct->name,
            'sku' => $secondProduct->sku,
            'quantity' => '1.000',
            'unit_price' => '100.00',
            'discount_percentage' => '0.00',
            'line_total' => '100.00',
        ]);

        $octoberSale = Sale::factory()->create([
            'customer_id' => $beta->id,
            'invoice_number' => 'SAL-REP-002',
            'sale_date' => '2026-10-05',
            'subtotal_amount' => '500.00',
            'discount_amount' => '0.00',
            'total_amount' => '500.00',
            'created_by' => $admin->id,
        ]);
        SaleItem::factory()->create([
            'sale_id' => $octoberSale->id,
            'product_id' => $firstProduct->id,
            'product_name' => $firstProduct->name,
            'sku' => $firstProduct->sku,
            'quantity' => '2.000',
            'unit_price' => '150.00',
            'discount_percentage' => '0.00',
            'line_total' => '300.00',
        ]);
        SaleItem::factory()->create([
            'sale_id' => $octoberSale->id,
            'product_id' => $secondProduct->id,
            'product_name' => $secondProduct->name,
            'sku' => $secondProduct->sku,
            'quantity' => '2.000',
            'unit_price' => '100.00',
            'discount_percentage' => '0.00',
            'line_total' => '200.00',
        ]);

        $oldSale = Sale::factory()->create([
            'customer_id' => $oldCustomer->id,
            'invoice_number' => 'SAL-REP-003',
            'sale_date' => '2025-12-20',
            'subtotal_amount' => '900.00',
            'discount_amount' => '0.00',
            'total_amount' => '900.00',
            'created_by' => $admin->id,
        ]);
        SaleItem::factory()->create([
            'sale_id' => $oldSale->id,
            'product_id' => $firstProduct->id,
            'product_name' => $firstProduct->name,
            'sku' => $firstProduct->sku,
            'quantity' => '6.000',
            'unit_price' => '150.00',
            'discount_percentage' => '0.00',
            'line_total' => '900.00',
        ]);

        $payments = app(PaymentService::class);

        $payments->create([
            'sale_id' => $novemberSale->id,
            'receipt_number' => 'PAY-REP-001',
            'payment_date' => '2026-11-12',
            'amount' => '300.00',
            'method' => Payment::METHOD_CASH,
            'reference' => null,
            'notes' => null,
        ], $admin);

        $payments->create([
            'sale_id' => $octoberSale->id,
            'receipt_number' => 'PAY-REP-002',
            'payment_date' => '2026-10-10',
            'amount' => '200.00',
            'method' => Payment::METHOD_BANK_TRANSFER,
            'reference' => null,
            'notes' => null,
        ], $admin);

        $payments->create([
            'sale_id' => $oldSale->id,
            'receipt_number' => 'PAY-REP-003',
            'payment_date' => '2025-12-25',
            'amount' => '100.00',
            'method' => Payment::METHOD_CARD,
            'reference' => null,
            'notes' => null,
        ], $admin);

        return [
            'admin' => $admin,
            'alpha' => $alpha,
            'beta' => $beta,
            'old_customer' => $oldCustomer,
            'first_product' => $firstProduct,
            'second_product' => $secondProduct,
            'empty_product' => $emptyProduct,
            'november_sale' => $novemberSale,
            'october_sale' => $octoberSale,
            'old_sale' => $oldSale,
        ];
    }
}
