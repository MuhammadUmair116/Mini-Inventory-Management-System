<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');
        $sku = $this->input('sku');
        $barcode = $this->input('barcode');

        $this->merge([
            'name' => is_string($name) ? trim($name) : $name,
            'sku' => is_string($sku) ? Str::upper(trim($sku)) : $sku,
            'barcode' => is_string($barcode) && $barcode !== ''
                ? Str::upper(trim($barcode))
                : null,
        ]);

        if ($this->has('is_active')) {
            $this->merge(['is_active' => $this->boolean('is_active')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:64', Rule::unique('products', 'sku')],
            'barcode' => ['nullable', 'string', 'max:64', Rule::unique('products', 'barcode')],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')->where('is_active', true),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'purchase_price' => ['required', 'numeric', 'min:0', 'decimal:2'],
            'sale_price' => ['required', 'numeric', 'min:0', 'decimal:2'],
            'reorder_level' => ['required', 'numeric', 'min:0', 'decimal:3'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
