<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\SaleItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_products(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_can_view_products(): void
    {
        Product::factory()->create();
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('products.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->has('products.data', 1)
                ->etc()
            );
    }

    public function test_staff_cannot_create_products(): void
    {
        $staff = User::factory()->create();
        $payload = $this->validProductData();

        $response = $this->actingAs($staff)->post(route('products.store'), $payload);

        $response->assertForbidden();
        $this->assertDatabaseMissing('products', ['sku' => $payload['sku']]);
    }

    public function test_admin_can_view_filtered_products_with_catalogue_options(): void
    {
        $category = Category::factory()->create(['name' => 'Electronics']);
        $unit = Unit::factory()->create(['name' => 'Piece', 'short_name' => 'PCS']);

        Product::factory()
            ->for($category)
            ->for($unit)
            ->create(['name' => 'Laptop Pro', 'sku' => 'ELEC-PRO-001']);

        Product::factory()->create(['name' => 'A4 Notebook', 'sku' => 'STAT-NOT-002']);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('products.index', ['search' => 'ELEC']));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Products/Index')
                ->where('filters.search', 'ELEC')
                ->has('products.data', 1)
                ->where('products.data.0.name', 'Laptop Pro')
                ->where('products.data.0.category.name', 'Electronics')
                ->where('products.data.0.unit.short_name', 'PCS')
                ->where('stats.total', 2)
                ->has('categoryOptions', 2)
                ->has('unitOptions', 2)
                ->etc()
            );
    }

    public function test_admin_can_create_a_product_and_cannot_set_initial_stock(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->validProductData([
            'name' => 'Wireless Mouse',
            'sku' => 'elec-mou-100',
            'barcode' => '8901000000999',
            'current_stock' => '999.000',
        ]);

        $response = $this->actingAs($admin)->post(route('products.store'), $payload);

        $response->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Product created successfully.');

        $this->assertDatabaseHas('products', [
            'name' => 'Wireless Mouse',
            'sku' => 'ELEC-MOU-100',
            'barcode' => '8901000000999',
            'current_stock' => 0,
            'purchase_price' => 100.00,
            'sale_price' => 150.00,
        ]);
    }

    public function test_sku_and_barcode_must_be_unique(): void
    {
        Product::factory()->create([
            'sku' => 'ELEC-DUP-001',
            'barcode' => '8901000000017',
        ]);

        $admin = User::factory()->admin()->create();
        $payload = $this->validProductData([
            'sku' => 'ELEC-DUP-001',
            'barcode' => '8901000000017',
        ]);

        $response = $this->actingAs($admin)->post(route('products.store'), $payload);

        $response->assertSessionHasErrors(['sku', 'barcode']);
    }

    public function test_product_must_reference_an_active_category_and_unit(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveCategory = Category::factory()->inactive()->create();
        $inactiveUnit = Unit::factory()->inactive()->create();

        $response = $this->actingAs($admin)->post(route('products.store'), $this->validProductData([
            'category_id' => $inactiveCategory->id,
            'unit_id' => $inactiveUnit->id,
        ]));

        $response->assertSessionHasErrors(['category_id', 'unit_id']);
    }

    public function test_admin_can_update_a_product_without_changing_current_stock(): void
    {
        $product = Product::factory()->create(['current_stock' => '25.000']);
        $admin = User::factory()->admin()->create();

        $payload = $this->validProductData([
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'name' => 'Updated Product Name',
            'current_stock' => '999.000',
        ]);

        $response = $this->actingAs($admin)->put(route('products.update', $product), $payload);

        $response->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Product updated successfully.');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'current_stock' => 25,
        ]);
    }

    public function test_admin_can_delete_a_product(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'))
            ->assertSessionHas('success', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_product_with_purchase_history_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        PurchaseItem::factory()->for($product)->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'))
            ->assertSessionHas('error', 'Product has transaction records and cannot be deleted.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_with_sales_history_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();
        SaleItem::factory()->for($product)->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'))
            ->assertSessionHas('error', 'Product has transaction records and cannot be deleted.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_product_policy_allows_staff_view_but_denies_mutations(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('create', Product::class));
        $this->assertTrue(Gate::forUser($staff)->allows('viewAny', Product::class));
        $this->assertFalse(Gate::forUser($staff)->allows('create', Product::class));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $product));
        $this->assertFalse(Gate::forUser($staff)->allows('delete', $product));
    }

    public function test_stock_status_scopes_and_accessor_classify_products_correctly(): void
    {
        $inStock = Product::factory()->create([
            'current_stock' => '20.000',
            'reorder_level' => '5.000',
        ]);
        $lowStock = Product::factory()->lowStock()->create();
        $outOfStock = Product::factory()->outOfStock()->create();

        $this->assertSame('in_stock', $inStock->stock_status);
        $this->assertSame('low_stock', $lowStock->stock_status);
        $this->assertSame('out_of_stock', $outOfStock->stock_status);
        $this->assertTrue($lowStock->is_low_stock);
        $this->assertSame(1, Product::inStock()->count());
        $this->assertSame(1, Product::lowStock()->count());
        $this->assertSame(1, Product::outOfStock()->count());
    }

    public function test_product_belongs_to_a_category_and_a_unit(): void
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();

        $product = Product::factory()->for($category)->for($unit)->create();

        $this->assertTrue($product->category->is($category));
        $this->assertTrue($product->unit->is($unit));
        $this->assertTrue($category->products->contains($product));
        $this->assertTrue($unit->products->contains($product));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validProductData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Product',
            'sku' => 'TEST-PRD-100',
            'barcode' => null,
            'category_id' => Category::factory()->create()->id,
            'unit_id' => Unit::factory()->create()->id,
            'description' => 'A product used for testing.',
            'purchase_price' => '100.00',
            'sale_price' => '150.00',
            'reorder_level' => '5.000',
            'is_active' => true,
        ], $overrides);
    }
}
