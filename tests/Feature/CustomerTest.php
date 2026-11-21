<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_customers(): void
    {
        $this->get(route('customers.index'))
            ->assertRedirect(route('login'));
    }

    public function test_staff_can_view_customers_but_cannot_mutate_them(): void
    {
        $customer = Customer::factory()->create();
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/Customers/Index')
                ->where('can.create', false)
                ->where('can.update', false)
                ->where('can.delete', false)
                ->etc()
            );

        $this->actingAs($staff)
            ->post(route('customers.store'), $this->validCustomerData())
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('customers.update', $customer), $this->validCustomerData())
            ->assertForbidden();

        $this->actingAs($staff)
            ->delete(route('customers.destroy', $customer))
            ->assertForbidden();
    }

    public function test_admin_can_view_filtered_customers_with_stats(): void
    {
        Customer::factory()->create(['name' => 'Green Valley Retail', 'email' => 'green@example.test']);
        Customer::factory()->inactive()->create(['name' => 'Old Customer', 'email' => 'old@example.test']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('customers.index', [
            'search' => 'Green',
            'is_active' => '1',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/Customers/Index')
                ->where('filters.search', 'Green')
                ->where('filters.is_active', '1')
                ->has('customers.data', 1)
                ->where('customers.data.0.name', 'Green Valley Retail')
                ->where('stats.total', 2)
                ->where('stats.active', 1)
                ->where('stats.inactive', 1)
                ->where('can.create', true)
                ->etc()
            );
    }

    public function test_admin_can_create_a_customer_with_normalized_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('customers.store'), $this->validCustomerData([
            'name' => '  Green Valley Retail  ',
            'email' => '  Sales@Example.test ',
        ]));

        $response->assertRedirect(route('customers.index'))
            ->assertSessionHas('success', 'Customer created successfully.');

        $this->assertDatabaseHas('customers', [
            'name' => 'Green Valley Retail',
            'email' => 'sales@example.test',
        ]);
    }

    public function test_customer_email_must_be_unique(): void
    {
        Customer::factory()->create(['email' => 'duplicate@example.test']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('customers.store'), $this->validCustomerData([
                'email' => 'DUPLICATE@example.test',
            ]))
            ->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_update_a_customer_without_conflicting_with_its_own_email(): void
    {
        $customer = Customer::factory()->create(['email' => 'original@example.test']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('customers.update', $customer), $this->validCustomerData([
                'name' => 'Updated Customer',
                'email' => 'ORIGINAL@example.test',
            ]))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success', 'Customer updated successfully.');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Customer',
            'email' => 'original@example.test',
        ]);
    }

    public function test_admin_can_delete_a_customer_without_sales_history(): void
    {
        $customer = Customer::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success', 'Customer deleted successfully.');

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_customer_with_sales_history_cannot_be_deleted(): void
    {
        $customer = Customer::factory()->create();
        $sale = Sale::factory()->for($customer)->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error', 'Customer has sales records and cannot be deleted.');

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    public function test_customer_policy_allows_both_roles_to_view_but_only_admin_to_mutate(): void
    {
        $customer = Customer::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Customer::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Customer::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $customer));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $customer));
        $this->assertTrue(Gate::forUser($staff)->allows('viewAny', Customer::class));
        $this->assertTrue(Gate::forUser($staff)->allows('view', $customer));
        $this->assertFalse(Gate::forUser($staff)->allows('create', Customer::class));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $customer));
        $this->assertFalse(Gate::forUser($staff)->allows('delete', $customer));
    }

    public function test_active_scope_excludes_inactive_customers(): void
    {
        Customer::factory()->create(['is_active' => true]);
        Customer::factory()->inactive()->create();

        $this->assertSame(1, Customer::active()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validCustomerData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Customer',
            'contact_person' => 'Sara Ahmed',
            'email' => 'customer@example.test',
            'phone' => '+92 300 7654321',
            'address' => '123 Test Street',
            'is_active' => true,
        ], $overrides);
    }
}
