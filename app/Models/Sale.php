<?php

namespace App\Models;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Database\Factories\SaleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'customer_id',
    'invoice_number',
    'sale_date',
    'status',
    'notes',
    'subtotal_amount',
    'discount_amount',
    'total_amount',
    'created_by',
])]
class Sale extends Model
{
    /** @use HasFactory<SaleFactory> */
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';

    public const PAYMENT_STATUS_PAID = 'paid';

    public const PAYMENT_STATUS_PARTIAL = 'partial';

    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    /** @var array<int, string> */
    protected $appends = ['paid_amount', 'due_amount', 'payment_status'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    /**
     * Get the amount already received for this sale.
     */
    protected function paidAmount(): Attribute
    {
        return Attribute::make(get: function (): string {
            $aggregate = $this->attributes['payments_sum_amount'] ?? null;
            $paid = $aggregate !== null
                ? BigDecimal::of((string) $aggregate)
                : BigDecimal::of((string) ($this->payments()->sum('amount') ?? '0'));

            return $paid->toScale(2, RoundingMode::HalfUp)->toString();
        });
    }

    /**
     * Get the remaining balance for this sale.
     */
    protected function dueAmount(): Attribute
    {
        return Attribute::make(get: function (): string {
            $total = BigDecimal::of((string) ($this->attributes['total_amount'] ?? '0'));
            $due = $total->minus(BigDecimal::of((string) $this->paid_amount))
                ->toScale(2, RoundingMode::HalfUp);

            return $due->isLessThan(0) ? '0.00' : $due->toString();
        });
    }

    /**
     * Get a displayable settlement status for this sale.
     */
    protected function paymentStatus(): Attribute
    {
        return Attribute::make(get: function (): string {
            if (BigDecimal::of((string) $this->due_amount)->isLessThanOrEqualTo(0)) {
                return self::PAYMENT_STATUS_PAID;
            }

            return BigDecimal::of((string) $this->paid_amount)->isGreaterThan(0)
                ? self::PAYMENT_STATUS_PARTIAL
                : self::PAYMENT_STATUS_UNPAID;
        });
    }

    #[Scope]
    protected function completed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
