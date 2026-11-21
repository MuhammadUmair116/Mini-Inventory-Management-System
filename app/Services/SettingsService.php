<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SettingsService
{
    public const KEY_APP_NAME = 'app_name';

    public const KEY_COMPANY_NAME = 'company_name';

    public const KEY_CONTACT_EMAIL = 'contact_email';

    public const KEY_CONTACT_PHONE = 'contact_phone';

    public const KEY_ADDRESS = 'address';

    public const KEY_CURRENCY_SYMBOL = 'currency_symbol';

    public const KEY_LOCALE = 'locale';

    /**
     * ISO 4217 codes supported by the display-only currency formatter.
     *
     * @var array<string, string>
     */
    public const CURRENCIES = [
        'PKR' => 'Pakistani Rupee',
        'USD' => 'US Dollar',
        'EUR' => 'Euro',
        'GBP' => 'British Pound',
        'INR' => 'Indian Rupee',
        'AED' => 'UAE Dirham',
        'SAR' => 'Saudi Riyal',
    ];

    /**
     * @var array<string, string>
     */
    public const LOCALES = [
        'en-US' => 'English (United States)',
        'en-GB' => 'English (United Kingdom)',
        'en-PK' => 'English (Pakistan)',
        'en-IN' => 'English (India)',
    ];

    /**
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        return [
            self::KEY_APP_NAME => 'Mini Inventory',
            self::KEY_COMPANY_NAME => 'Inventory Manager',
            self::KEY_CONTACT_EMAIL => '',
            self::KEY_CONTACT_PHONE => '',
            self::KEY_ADDRESS => '',
            self::KEY_CURRENCY_SYMBOL => 'PKR',
            self::KEY_LOCALE => 'en-US',
        ];
    }

    /**
     * Return saved settings merged with defaults for keys that are not stored yet.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $stored = Setting::query()->pluck('value', 'key')->all();
        $settings = [];

        foreach (self::defaults() as $key => $default) {
            $settings[$key] = array_key_exists($key, $stored) && $stored[$key] !== null
                ? (string) $stored[$key]
                : $default;
        }

        return $settings;
    }

    /**
     * Persist only recognized setting keys.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    public function update(array $values): array
    {
        return DB::transaction(function () use ($values): array {
            foreach ($values as $key => $value) {
                if (! array_key_exists($key, self::defaults())) {
                    continue;
                }

                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => (string) ($value ?? '')],
                );
            }

            return $this->all();
        });
    }

    /**
     * @return array{currencies: array<int, array{value: string, label: string}>, locales: array<int, array{value: string, label: string}>}
     */
    public function options(): array
    {
        return [
            'currencies' => $this->optionList(self::CURRENCIES),
            'locales' => $this->optionList(self::LOCALES),
        ];
    }

    /**
     * @param  array<string, string>  $values
     * @return array<int, array{value: string, label: string}>
     */
    private function optionList(array $values): array
    {
        $options = [];

        foreach ($values as $value => $label) {
            $options[] = [
                'value' => (string) $value,
                'label' => "{$value} — {$label}",
            ];
        }

        return $options;
    }
}
