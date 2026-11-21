<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_categories(): void
    {
        $response = $this->get(route('categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_view_categories(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('categories.index'));

        $response->assertForbidden();
    }

    public function test_staff_cannot_create_categories(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('categories.store'), [
            'name' => 'Forbidden Category',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('categories', ['name' => 'Forbidden Category']);
    }

    public function test_admin_can_view_filtered_categories(): void
    {
        Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);
        Category::factory()->create(['name' => 'Groceries', 'slug' => 'groceries']);
        Category::factory()->create(['name' => 'Stationery', 'slug' => 'stationery']);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('categories.index', ['search' => 'Groc']));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/Categories/Index')
                ->where('filters.search', 'Groc')
                ->has('categories.data', 1)
                ->where('categories.data.0.name', 'Groceries')
                ->etc()
            );
    }

    public function test_admin_can_create_a_category(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Office Supplies',
            'description' => 'Items used in the office.',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('categories.index'))
            ->assertSessionHas('success', 'Category created successfully.');

        $this->assertDatabaseHas('categories', [
            'name' => 'Office Supplies',
            'slug' => 'office-supplies',
            'description' => 'Items used in the office.',
            'is_active' => 1,
        ]);
    }

    public function test_category_name_and_slug_must_be_unique(): void
    {
        Category::factory()->create(['name' => 'Electronics', 'slug' => 'electronics']);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('categories.store'), [
            'name' => 'Electronics',
        ]);

        $response->assertSessionHasErrors(['name', 'slug']);
    }

    public function test_admin_can_update_a_category_without_changing_its_slug(): void
    {
        $category = Category::factory()->create([
            'name' => 'Office Supplies',
            'slug' => 'office-supplies',
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('categories.update', $category), [
            'name' => 'Office Supplies',
            'description' => 'Updated description.',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('categories.index'))
            ->assertSessionHas('success', 'Category updated successfully.');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'Office Supplies',
            'slug' => 'office-supplies',
            'description' => 'Updated description.',
            'is_active' => 0,
        ]);
    }

    public function test_admin_can_delete_a_category(): void
    {
        $category = Category::factory()->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'))
            ->assertSessionHas('success', 'Category deleted successfully.');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_policy_allows_admins_and_denies_staff(): void
    {
        $category = Category::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('update', $category));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $category));
    }
}
