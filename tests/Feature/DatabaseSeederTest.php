<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_seeder_creates_a_consistent_demo_dataset(): void
    {
        $this->seed();

        $this->assertSame(7, Setting::count());
        $this->assertSame(2, User::count());
        $this->assertSame(4, Category::count());
        $this->assertSame(5, Unit::count());
        $this->assertSame(5, Product::count());
        $this->assertSame(3, Supplier::count());
        $this->assertSame(2, Purchase::count());
        $this->assertSame(4, PurchaseItem::count());
        $this->assertSame(3, Customer::count());
        $this->assertSame(2, Sale::count());
        $this->assertSame(4, SaleItem::count());
        $this->assertSame(2, Payment::count());
        $this->assertSame(8, StockMovement::count());

        $partialSale = Sale::where('invoice_number', 'SAL-2026-0001')->firstOrFail();

        $this->assertSame('277800.00', $partialSale->total_amount);
        $this->assertSame('100000.00', $partialSale->paid_amount);
        $this->assertSame('177800.00', $partialSale->due_amount);
        $this->assertSame(Sale::PAYMENT_STATUS_PARTIAL, $partialSale->payment_status);

        $settledSale = Sale::where('invoice_number', 'SAL-2026-0002')->firstOrFail();

        $this->assertSame('28550.00', $settledSale->paid_amount);
        $this->assertSame('0.00', $settledSale->due_amount);
        $this->assertSame(Sale::PAYMENT_STATUS_PAID, $settledSale->payment_status);
    }

    public function test_seeded_admin_can_render_every_admin_page_with_demo_data(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@inventory.test')->firstOrFail();

        foreach ($this->adminPages() as $routeName => $component) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component($component)
                    ->where('settings.app_name', 'Mini Inventory')
                    ->etc()
                );
        }

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('stats.outstanding_total', '177800.00')
                ->has('recent_sales', 2)
                ->has('recent_payments', 2)
                ->etc()
            );

        $this->actingAs($admin)
            ->get(route('inventory.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('products.data', 5)
                ->has('movements.data', 8)
                ->etc()
            );
    }

    /**
     * @return array<string, string>
     */
    private function adminPages(): array
    {
        return [
            'dashboard' => 'Dashboard/Index',
            'products.index' => 'Products/Index',
            'customers.index' => 'MasterData/Customers/Index',
            'sales.index' => 'Sales/Index',
            'inventory.index' => 'Inventory/Index',
            'payments.index' => 'Payments/Index',
            'reports.sales' => 'Reports/Sales',
            'purchases.index' => 'Purchases/Index',
            'suppliers.index' => 'MasterData/Suppliers/Index',
            'categories.index' => 'MasterData/Categories/Index',
            'units.index' => 'MasterData/Units/Index',
            'users.index' => 'Users/Index',
            'settings.index' => 'Settings/Index',
        ];
    }
}
