<?php

namespace App\Http\Requests;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', Setting::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'app_name' => $this->trimInput('app_name'),
            'company_name' => $this->trimInput('company_name'),
            'contact_email' => $this->trimInput('contact_email'),
            'contact_phone' => $this->trimInput('contact_phone'),
            'address' => $this->trimInput('address'),
            'currency_symbol' => Str::upper((string) $this->trimInput('currency_symbol')),
            'locale' => $this->trimInput('locale'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'company_name' => ['required', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'currency_symbol' => ['required', Rule::in(array_keys(SettingsService::CURRENCIES))],
            'locale' => ['required', Rule::in(array_keys(SettingsService::LOCALES))],
        ];
    }

    private function trimInput(string $key): ?string
    {
        $value = $this->input($key);

        return is_string($value) ? trim($value) : null;
    }
}
