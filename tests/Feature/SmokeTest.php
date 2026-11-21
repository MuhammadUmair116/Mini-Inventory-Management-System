<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_staff_accessible_pages_render_for_an_authenticated_staff_member(): void
    {
        $staff = User::factory()->create();

        foreach ($this->staffPages() as $routeName => $component) {
            $this->actingAs($staff)
                ->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    public function test_all_admin_pages_render_for_an_authenticated_admin(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ($this->adminPages() as $routeName => $component) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page->component($component));
        }
    }

    public function test_admin_only_pages_are_forbidden_for_staff(): void
    {
        $staff = User::factory()->create();

        foreach (array_keys($this->adminOnlyPages()) as $routeName) {
            $this->actingAs($staff)
                ->get(route($routeName))
                ->assertForbidden();
        }
    }

    /**
     * @return array<string, string>
     */
    private function adminPages(): array
    {
        return array_merge($this->staffPages(), $this->adminOnlyPages());
    }

    /**
     * @return array<string, string>
     */
    private function staffPages(): array
    {
        return [
            'dashboard' => 'Dashboard/Index',
            'products.index' => 'Products/Index',
            'customers.index' => 'MasterData/Customers/Index',
            'sales.index' => 'Sales/Index',
            'sales.create' => 'Sales/Create',
            'inventory.index' => 'Inventory/Index',
            'payments.index' => 'Payments/Index',
            'reports.sales' => 'Reports/Sales',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function adminOnlyPages(): array
    {
        return [
            'categories.index' => 'MasterData/Categories/Index',
            'units.index' => 'MasterData/Units/Index',
            'suppliers.index' => 'MasterData/Suppliers/Index',
            'purchases.index' => 'Purchases/Index',
            'purchases.create' => 'Purchases/Create',
            'users.index' => 'Users/Index',
            'settings.index' => 'Settings/Index',
        ];
    }
}
