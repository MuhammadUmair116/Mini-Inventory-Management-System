<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Payment::class) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $receiptNumber = $this->input('receipt_number');
        $reference = $this->input('reference');
        $notes = $this->input('notes');

        $this->merge([
            'receipt_number' => is_string($receiptNumber) ? Str::upper(trim($receiptNumber)) : $receiptNumber,
            'reference' => is_string($reference) && $reference !== '' ? trim($reference) : null,
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
            'sale_id' => ['required', 'integer', Rule::exists('sales', 'id')],
            'receipt_number' => [
                'required',
                'string',
                'max:64',
                Rule::unique('payments', 'receipt_number'),
            ],
            'payment_date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99', 'decimal:0,2'],
            'method' => ['required', 'string', Rule::in(array_keys(Payment::methods()))],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
