<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Sale::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $invoiceNumber = $this->input('invoice_number');
        $notes = $this->input('notes');
        $items = $this->input('items');

        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (! array_key_exists('discount_percentage', $item) || $item['discount_percentage'] === '') {
                    $items[$index]['discount_percentage'] = '0.00';
                }
            }
        }

        $this->merge([
            'invoice_number' => is_string($invoiceNumber)
                ? Str::upper(trim($invoiceNumber))
                : $invoiceNumber,
            'notes' => is_string($notes) && $notes !== '' ? trim($notes) : null,
            'items' => $items,
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
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('is_active', true),
            ],
            'invoice_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('sales', 'invoice_number'),
            ],
            'sale_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'decimal:1,3'],
            'items.*.discount_percentage' => ['required', 'numeric', 'min:0', 'max:100', 'decimal:0,2'],
        ];
    }
}
