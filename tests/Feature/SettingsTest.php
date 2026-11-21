<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_when_viewing_settings(): void
    {
        $response = $this->get(route('settings.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_staff_cannot_view_settings(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->get(route('settings.index'));

        $response->assertForbidden();
    }

    public function test_staff_cannot_update_settings(): void
    {
        $staff = User::factory()->create();

        $response = $this->actingAs($staff)->put(route('settings.update'), $this->validSettingsData());

        $response->assertForbidden();
        $this->assertDatabaseMissing('settings', ['key' => SettingsService::KEY_COMPANY_NAME]);
    }

    public function test_inactive_administrators_cannot_view_settings(): void
    {
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        $response = $this->actingAs($inactiveAdmin)->get(route('settings.index'));

        $response->assertForbidden();
    }

    public function test_setting_policy_allows_only_active_administrators(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->create();
        $inactiveAdmin = User::factory()->admin()->inactive()->create();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', Setting::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', Setting::class));
        $this->assertFalse(Gate::forUser($staff)->allows('viewAny', Setting::class));
        $this->assertFalse(Gate::forUser($inactiveAdmin)->allows('viewAny', Setting::class));
    }

    public function test_admin_sees_default_settings_and_form_options(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('settings.app_name', 'Mini Inventory')
                ->where('settings.currency_symbol', 'PKR')
                ->where('settings.locale', 'en-US')
                ->has('options.currencies', 7)
                ->has('options.locales', 4)
                ->etc()
            );
    }

    public function test_setting_seeder_persists_default_values(): void
    {
        $this->seed(SettingSeeder::class);

        $this->assertDatabaseHas('settings', [
            'key' => SettingsService::KEY_APP_NAME,
            'value' => 'Mini Inventory',
        ]);
        $this->assertSame(7, Setting::count());
    }

    public function test_admin_can_update_settings_and_shared_props_reflect_saved_values(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('settings.update'), $this->validSettingsData([
            'app_name' => 'Stockroom',
            'company_name' => 'Acme Store',
            'contact_email' => 'ops@acme.test',
            'currency_symbol' => 'USD',
            'locale' => 'en-GB',
        ]));

        $response->assertRedirect(route('settings.index'))
            ->assertSessionHas('success', 'Settings updated successfully.');

        $this->assertDatabaseHas('settings', [
            'key' => SettingsService::KEY_COMPANY_NAME,
            'value' => 'Acme Store',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => SettingsService::KEY_CURRENCY_SYMBOL,
            'value' => 'USD',
        ]);
        $this->assertSame(7, Setting::count());

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('settings.app_name', 'Stockroom')
                ->where('settings.company_name', 'Acme Store')
                ->where('settings.contact_email', 'ops@acme.test')
                ->where('settings.currency_symbol', 'USD')
                ->where('settings.locale', 'en-GB')
                ->etc()
            );
    }

    public function test_settings_update_rejects_unsupported_currency_and_locale(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('settings.update'), $this->validSettingsData([
            'currency_symbol' => 'XYZ',
            'locale' => 'xx-YY',
        ]));

        $response->assertSessionHasErrors(['currency_symbol', 'locale']);
        $this->assertDatabaseMissing('settings', ['key' => SettingsService::KEY_COMPANY_NAME]);
    }

    public function test_settings_update_requires_identity_fields_and_a_valid_email(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('settings.update'), $this->validSettingsData([
            'app_name' => '',
            'company_name' => '',
            'contact_email' => 'not-an-email',
        ]));

        $response->assertSessionHasErrors(['app_name', 'company_name', 'contact_email']);
        $this->assertDatabaseMissing('settings', ['key' => SettingsService::KEY_APP_NAME]);
    }

    public function test_settings_update_ignores_unrecognized_keys(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->put(route('settings.update'), [
            ...$this->validSettingsData(),
            'api_key' => 'should-not-be-stored',
            'role' => 'admin',
        ]);

        $response->assertRedirect(route('settings.index'));
        $this->assertDatabaseMissing('settings', ['key' => 'api_key']);
        $this->assertDatabaseMissing('settings', ['key' => 'role']);
        $this->assertSame(7, Setting::count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validSettingsData(array $overrides = []): array
    {
        return array_merge([
            'app_name' => 'Mini Inventory',
            'company_name' => 'Inventory Manager',
            'contact_email' => '',
            'contact_phone' => '',
            'address' => '',
            'currency_symbol' => 'PKR',
            'locale' => 'en-US',
        ], $overrides);
    }
}
