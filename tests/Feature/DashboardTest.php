<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo('2026-11-17 12:00:00');
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_active_staff_can_view_the_dashboard_with_business_overview(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('stats.sales_today', 0)
                ->where('stats.sales_this_month', 0)
                ->where('stats.payments_this_month', 0)
                ->where('stats.outstanding_total', '0.00')
                ->where('stats.active_products', 0)
                ->has('trend', 6)
                ->has('low_stock_products', 0)
                ->has('recent_sales', 0)
                ->has('recent_payments', 0)
                ->etc()
            );
    }

    public function test_admin_can_view_the_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('stats')
                ->has('trend')
                ->etc()
            );
    }

    public function test_inactive_users_cannot_view_the_dashboard(): void
    {
        $inactiveUser = User::factory()->inactive()->create();

        $response = $this->actingAs($inactiveUser)->get(route('dashboard'));

        $response->assertForbidden();
    }

    public function test_dashboard_calculates_sales_and_payment_stats_for_today_and_month(): void
    {
        $admin = User::factory()->admin()->create();
        $todaySale = Sale::factory()->create([
            'sale_date' => '2026-11-17',
            'total_amount' => '1000.00',
            'created_by' => $admin->id,
        ]);
        Sale::factory()->create([
            'sale_date' => '2026-11-02',
            'total_amount' => '500.00',
            'created_by' => $admin->id,
        ]);
        $lastMonthSale = Sale::factory()->create([
            'sale_date' => '2026-10-15',
            'total_amount' => '700.00',
            'created_by' => $admin->id,
        ]);

        app(PaymentService::class)->create($this->paymentData($todaySale, 'PAY-DASH-001', '2026-11-10', '300.00'), $admin);
        app(PaymentService::class)->create($this->paymentData($lastMonthSale, 'PAY-DASH-002', '2026-10-20', '100.00'), $admin);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('stats.sales_today', 1)
                ->where('stats.sales_today_total', '1000.00')
                ->where('stats.sales_this_month', 2)
                ->where('stats.sales_this_month_total', '1500.00')
                ->where('stats.payments_this_month', 1)
                ->where('stats.payments_this_month_total', '300.00')
                ->where('stats.outstanding_total', '1800.00')
                ->etc()
            );
    }

    public function test_dashboard_shows_low_stock_alerts_and_stock_health(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->create([
            'sku' => 'DASH-IN',
            'current_stock' => '10.000',
            'reorder_level' => '5.000',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'sku' => 'DASH-LOW',
            'current_stock' => '2.000',
            'reorder_level' => '5.000',
            'is_active' => true,
        ]);
        Product::factory()->create([
            'sku' => 'DASH-OUT',
            'current_stock' => '0.000',
            'reorder_level' => '5.000',
            'is_active' => true,
        ]);
        Product::factory()->inactive()->create([
            'sku' => 'DASH-OFF',
            'current_stock' => '1.000',
            'reorder_level' => '5.000',
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->where('stats.active_products', 3)
                ->where('stats.in_stock_products', 1)
                ->where('stats.low_stock_products', 1)
                ->where('stats.out_of_stock_products', 1)
                ->has('low_stock_products', 1)
                ->where('low_stock_products.0.sku', 'DASH-LOW')
                ->where('low_stock_products.0.current_stock', '2.000')
                ->where('low_stock_products.0.reorder_level', '5.000')
                ->where('low_stock_products.0.stock_status', 'low_stock')
                ->etc()
            );
    }

    public function test_dashboard_shows_recent_sales_with_settlement_status(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['name' => 'Dashboard Customer']);

        foreach (range(1, 6) as $day) {
            Sale::factory()->create([
                'customer_id' => $customer->id,
                'invoice_number' => sprintf('SAL-DASH-%03d', $day),
                'sale_date' => sprintf('2026-11-%02d', $day),
                'total_amount' => '500.00',
                'created_by' => $admin->id,
            ]);
        }
        $newestSale = Sale::query()->where('invoice_number', 'SAL-DASH-006')->firstOrFail();
        app(PaymentService::class)->create($this->paymentData($newestSale, 'PAY-DASH-SALE', '2026-11-17', '500.00'), $admin);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('recent_sales', 5)
                ->where('recent_sales.0.invoice_number', 'SAL-DASH-006')
                ->where('recent_sales.0.customer_name', 'Dashboard Customer')
                ->where('recent_sales.0.sale_date', '2026-11-06')
                ->where('recent_sales.0.total_amount', '500.00')
                ->where('recent_sales.0.paid_amount', '500.00')
                ->where('recent_sales.0.due_amount', '0.00')
                ->where('recent_sales.0.payment_status', Sale::PAYMENT_STATUS_PAID)
                ->etc()
            );
    }

    public function test_dashboard_shows_recent_payments_with_sale_and_creator(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = Customer::factory()->create(['name' => 'Receipt Customer']);

        foreach (range(1, 6) as $day) {
            $sale = Sale::factory()->create([
                'customer_id' => $customer->id,
                'invoice_number' => sprintf('SAL-DASHP-%03d', $day),
                'sale_date' => '2026-11-01',
                'total_amount' => '1000.00',
                'created_by' => $admin->id,
            ]);
            app(PaymentService::class)->create($this->paymentData(
                $sale,
                sprintf('PAY-DASHP-%03d', $day),
                sprintf('2026-11-%02d', $day),
                '100.00',
            ), $admin);
        }

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('recent_payments', 5)
                ->where('recent_payments.0.receipt_number', 'PAY-DASHP-006')
                ->where('recent_payments.0.invoice_number', 'SAL-DASHP-006')
                ->where('recent_payments.0.customer_name', 'Receipt Customer')
                ->where('recent_payments.0.payment_date', '2026-11-06')
                ->where('recent_payments.0.amount', '100.00')
                ->where('recent_payments.0.method', Payment::METHOD_CASH)
                ->where('recent_payments.0.method_label', 'Cash')
                ->where('recent_payments.0.recorded_by', $admin->name)
                ->etc()
            );
    }

    public function test_dashboard_trend_covers_the_last_six_months(): void
    {
        $admin = User::factory()->admin()->create();
        Sale::factory()->create([
            'sale_date' => '2026-09-10',
            'total_amount' => '400.00',
            'created_by' => $admin->id,
        ]);
        Sale::factory()->create([
            'sale_date' => '2026-11-10',
            'total_amount' => '900.00',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard/Index')
                ->has('trend', 6)
                ->where('trend.0.period', '2026-06')
                ->where('trend.0.sales', '0.00')
                ->where('trend.3.period', '2026-09')
                ->where('trend.3.sales', '400.00')
                ->where('trend.5.period', '2026-11')
                ->where('trend.5.sales', '900.00')
                ->etc()
            );
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentData(Sale $sale, string $receiptNumber, string $paymentDate, string $amount): array
    {
        return [
            'sale_id' => $sale->id,
            'receipt_number' => $receiptNumber,
            'payment_date' => $paymentDate,
            'amount' => $amount,
            'method' => Payment::METHOD_CASH,
            'reference' => null,
            'notes' => null,
        ];
    }
}
