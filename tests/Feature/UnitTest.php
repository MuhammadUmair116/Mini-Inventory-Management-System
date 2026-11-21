<?php

namespace Tests\Feature;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_units(): void
    {
        $response = $this->get(route('units.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_view_units(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('units.index'));

        $response->assertForbidden();
    }

    public function test_staff_cannot_create_units(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('units.store'), [
            'name' => 'Forbidden Unit',
            'short_name' => 'FRB',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('units', ['name' => 'Forbidden Unit']);
    }

    public function test_admin_can_view_filtered_units(): void
    {
        Unit::factory()->create(['name' => 'Piece', 'short_name' => 'PCS']);
        Unit::factory()->create(['name' => 'Kilogram', 'short_name' => 'KG']);
        Unit::factory()->create(['name' => 'Liter', 'short_name' => 'LTR']);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('units.index', ['search' => 'KG']));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MasterData/Units/Index')
                ->where('filters.search', 'KG')
                ->has('units.data', 1)
                ->where('units.data.0.name', 'Kilogram')
                ->etc()
            );
    }

    public function test_admin_can_create_a_unit(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('units.store'), [
            'name' => 'Centimeter',
            'short_name' => 'cm',
            'description' => 'Metric unit of length.',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('units.index'))
            ->assertSessionHas('success', 'Unit created successfully.');

        $this->assertDatabaseHas('units', [
            'name' => 'Centimeter',
            'short_name' => 'CM',
            'description' => 'Metric unit of length.',
            'is_active' => 1,
        ]);
    }

    public function test_unit_name_and_short_name_must_be_unique(): void
    {
        Unit::factory()->create(['name' => 'Piece', 'short_name' => 'PCS']);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('units.store'), [
            'name' => 'Piece',
            'short_name' => 'PCS',
        ]);

        $response->assertSessionHasErrors(['name', 'short_name']);
    }

    public function test_admin_can_update_a_unit(): void
    {
        $unit = Unit::factory()->create([
            'name' => 'Piece',
            'short_name' => 'PCS',
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('units.update', $unit), [
            'name' => 'Piece',
            'short_name' => 'PCS',
            'description' => 'A single countable item.',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('units.index'))
            ->assertSessionHas('success', 'Unit updated successfully.');

        $this->assertDatabaseHas('units', [
            'id' => $unit->id,
            'name' => 'Piece',
            'short_name' => 'PCS',
            'description' => 'A single countable item.',
            'is_active' => 0,
        ]);
    }

    public function test_admin_can_delete_a_unit(): void
    {
        $unit = Unit::factory()->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete(route('units.destroy', $unit));

        $response->assertRedirect(route('units.index'))
            ->assertSessionHas('success', 'Unit deleted successfully.');

        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_unit_policy_allows_admins_and_denies_staff(): void
    {
        $unit = Unit::factory()->create();
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('update', $unit));
        $this->assertFalse(Gate::forUser($staff)->allows('update', $unit));
    }
}
