<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Purchase::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $invoiceNumber = $this->input('invoice_number');
        $notes = $this->input('notes');

        $this->merge([
            'invoice_number' => is_string($invoiceNumber)
                ? Str::upper(trim($invoiceNumber))
                : $invoiceNumber,
            'notes' => is_string($notes) && $notes !== '' ? trim($notes) : null,
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
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')->where('is_active', true),
            ],
            'invoice_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('purchases', 'invoice_number'),
            ],
            'purchase_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct:strict',
                Rule::exists('products', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'decimal:1,3'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'decimal:2'],
        ];
    }
}
