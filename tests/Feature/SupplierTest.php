<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SupplierTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_suppliers(): void
    {
        $response = $this->get(route('suppliers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_view_suppliers(): void
    {
        $staff = User::factory()->create();

        $this->actingAs($staff)
            ->get(route('suppliers.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_filtered_suppliers_with_stats(): void
    {
        Supplier::factory()->create(['name' => 'Tech World Supplies', 'email' => 'tech@example.test']);
        Supplier::factory()->inactive()->create(['name' => 'Old Vendor', 'email' => 'old@example.test']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('suppliers.index', [
            'search' => 'Tech',
            'is_active' => '1',
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/Suppliers/Index')
                ->where('filters.search', 'Tech')
                ->where('filters.is_active', '1')
                ->has('suppliers.data', 1)
                ->where('suppliers.data.0.name', 'Tech World Supplies')
                ->where('stats.total', 2)
                ->where('stats.active', 1)
                ->where('stats.inactive', 1)
                ->etc()
            );
    }

    public function test_admin_can_create_a_supplier_with_normalized_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('suppliers.store'), $this->validSupplierData([
            'name' => '  Tech World Supplies  ',
            'email' => '  Sales@Example.test ',
        ]));

        $response->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success', 'Supplier created successfully.');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Tech World Supplies',
            'email' => 'sales@example.test',
        ]);
    }

    public function test_supplier_email_must_be_unique(): void
    {
        Supplier::factory()->create(['email' => 'duplicate@example.test']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('suppliers.store'), $this->validSupplierData([
                'email' => 'DUPLICATE@example.test',
            ]))
            ->assertSessionHasErrors(['email']);
    }

    public function test_admin_can_update_a_supplier_without_conflicting_with_its_own_email(): void
    {
        $supplier = Supplier::factory()->create(['email' => 'original@example.test']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->put(route('suppliers.update', $supplier), $this->validSupplierData([
                'name' => 'Updated Supplier',
                'email' => 'ORIGINAL@example.test',
            ]))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success', 'Supplier updated successfully.');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Updated Supplier',
            'email' => 'original@example.test',
        ]);
    }

    public function test_admin_can_delete_a_supplier_without_purchase_history(): void
    {
        $supplier = Supplier::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('success', 'Supplier deleted successfully.');

        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_supplier_with_purchase_history_cannot_be_deleted(): void
    {
        $supplier = Supplier::factory()->create();
        $purchase = Purchase::factory()->for($supplier)->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->delete(route('suppliers.destroy', $supplier))
            ->assertRedirect(route('suppliers.index'))
            ->assertSessionHas('error', 'Supplier has purchase records and cannot be deleted.');

        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id]);
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id]);
    }

    public function test_supplier_policy_allows_admin_only(): void
    {
        $supplier = Supplier::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Supplier::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', Supplier::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $supplier));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $supplier));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Supplier::class));
        $this->assertFalse(Gate::forUser($staff)->allows('view', $supplier));
        $this->assertFalse(Gate::forUser($staff)->allows('create', Supplier::class));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $supplier));
        $this->assertFalse(Gate::forUser($staff)->allows('delete', $supplier));
    }

    public function test_active_scope_excludes_inactive_suppliers(): void
    {
        Supplier::factory()->create(['is_active' => true]);
        Supplier::factory()->inactive()->create();

        $this->assertSame(1, Supplier::active()->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validSupplierData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Supplier',
            'contact_person' => 'Ali Hassan',
            'email' => 'supplier@example.test',
            'phone' => '+92 300 1234567',
            'address' => '123 Test Street',
            'is_active' => true,
        ], $overrides);
    }
}
