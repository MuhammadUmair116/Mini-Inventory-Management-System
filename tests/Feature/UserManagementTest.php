<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_users(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_view_users(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_staff_cannot_create_users(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->post(route('users.store'), $this->validUserData());

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'new.user@example.test']);
    }

    public function test_staff_cannot_update_users(): void
    {
        $staff = User::factory()->create();
        $target = User::factory()->create(['name' => 'Staff Member']);

        $response = $this->actingAs($staff)->patch(route('users.update', $target), $this->validUserData([
            'email' => $target->email,
        ]));

        $response->assertForbidden();
        $this->assertSame('Staff Member', $target->fresh()->name);
    }

    public function test_inactive_administrators_cannot_view_users(): void
    {
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        $response = $this->actingAs($inactiveAdmin)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_user_policy_allows_only_active_administrators(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', User::class));
        $this->assertFalse(Gate::forUser($inactiveAdmin)->allows('viewAny', User::class));
    }

    public function test_admin_can_view_filtered_users_with_statistics_and_options(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Root Admin']);
        User::factory()->create(['name' => 'Ava Staff', 'email' => 'ava@example.test']);
        User::factory()->admin()->create(['name' => 'Ben Admin']);
        User::factory()->inactive()->create(['name' => 'Idle Staff']);

        $response = $this->actingAs($admin)->get(route('users.index', [
            'search' => 'ava',
            'role' => User::ROLE_STAFF,
        ]));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Users/Index')
                ->has('users.data', 1)
                ->where('users.total', 1)
                ->where('users.data.0.name', 'Ava Staff')
                ->where('users.data.0.role', User::ROLE_STAFF)
                ->where('filters.search', 'ava')
                ->where('filters.role', User::ROLE_STAFF)
                ->where('stats.total', 4)
                ->where('stats.admins', 2)
                ->where('stats.staff', 2)
                ->where('stats.inactive', 1)
                ->has('roleOptions', 2)
                ->has('statusOptions', 2)
                ->etc()
            );
    }

    public function test_admin_can_create_a_user_with_hashed_password_and_verified_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), $this->validUserData([
            'email' => ' NEW.USER@EXAMPLE.TEST ',
        ]));

        $response->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User created successfully.');

        $created = User::where('email', 'new.user@example.test')->firstOrFail();

        $this->assertSame('New User', $created->name);
        $this->assertSame(User::ROLE_STAFF, $created->role);
        $this->assertSame(User::STATUS_ACTIVE, $created->status);
        $this->assertNotNull($created->email_verified_at);
        $this->assertTrue(Hash::check('Secret123!', $created->password));
    }

    public function test_create_user_rejects_a_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'taken@example.test']);

        $response = $this->actingAs($admin)->post(route('users.store'), $this->validUserData([
            'email' => 'TAKEN@example.test',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertSame(2, User::count());
    }

    public function test_create_user_rejects_invalid_role_status_and_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), $this->validUserData([
            'role' => 'owner',
            'status' => 'banned',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]));

        $response->assertSessionHasErrors(['role', 'status', 'password']);
        $this->assertSame(1, User::count());
    }

    public function test_create_user_requires_a_confirmed_password(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('users.store'), $this->validUserData([
            'password_confirmation' => 'Different123!',
        ]));

        $response->assertSessionHasErrors('password');
        $this->assertSame(1, User::count());
    }

    public function test_admin_can_update_user_details_role_status_and_password(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create([
            'name' => 'Staff Member',
            'email' => 'staff.member@example.test',
        ]);

        $response = $this->actingAs($admin)->patch(route('users.update', $target), [
            'name' => 'Senior Staff',
            'email' => 'senior.staff@example.test',
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_INACTIVE,
            'password' => 'Updated123!',
            'password_confirmation' => 'Updated123!',
        ]);

        $response->assertRedirect(route('users.index'))
            ->assertSessionHas('success', 'User updated successfully.');

        $updated = $target->fresh();

        $this->assertSame('Senior Staff', $updated->name);
        $this->assertSame('senior.staff@example.test', $updated->email);
        $this->assertSame(User::ROLE_ADMIN, $updated->role);
        $this->assertSame(User::STATUS_INACTIVE, $updated->status);
        $this->assertTrue(Hash::check('Updated123!', $updated->password));
    }

    public function test_admin_can_update_a_user_without_replacing_the_password(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        $originalPassword = $target->password;

        $response = $this->actingAs($admin)->patch(route('users.update', $target), [
            'name' => 'Renamed Staff',
            'email' => $target->email,
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('users.index'));

        $updated = $target->fresh();

        $this->assertSame('Renamed Staff', $updated->name);
        $this->assertSame($originalPassword, $updated->password);
        $this->assertTrue(Hash::check('password', $updated->password));
    }

    public function test_update_user_rejects_an_email_used_by_another_account(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();
        $other = User::factory()->create(['email' => 'other@example.test']);

        $response = $this->actingAs($admin)->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $other->email,
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame($target->email, $target->fresh()->email);
    }

    public function test_update_user_rejects_invalid_role_and_status(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'owner',
            'status' => 'banned',
        ]);

        $response->assertSessionHasErrors(['role', 'status']);
        $this->assertSame(User::ROLE_STAFF, $target->fresh()->role);
        $this->assertSame(User::STATUS_ACTIVE, $target->fresh()->status);
    }

    public function test_last_active_admin_cannot_be_demoted(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasErrors(['role', 'status']);
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);
    }

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch(route('users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_INACTIVE,
        ]);

        $response->assertSessionHasErrors(['role', 'status']);
        $this->assertSame(User::STATUS_ACTIVE, $admin->fresh()->status);
    }

    public function test_admin_can_demote_another_admin_when_a_second_active_admin_remains(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->patch(route('users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame(User::ROLE_STAFF, $target->fresh()->role);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUserData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New User',
            'email' => 'new.user@example.test',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'role' => User::ROLE_STAFF,
            'status' => User::STATUS_ACTIVE,
        ], $overrides);
    }
}
