<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'unit_id',
    'name',
    'sku',
    'barcode',
    'description',
    'purchase_price',
    'sale_price',
    'reorder_level',
    'is_active',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /** @var array<int, string> */
    protected $appends = ['stock_status', 'is_low_stock'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'current_stock' => 'decimal:3',
            'reorder_level' => 'decimal:3',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the human-readable stock status.
     */
    protected function stockStatus(): Attribute
    {
        return Attribute::make(get: function (): string {
            $stock = (float) $this->current_stock;
            $reorderLevel = (float) $this->reorder_level;

            if ($stock <= 0) {
                return 'out_of_stock';
            }

            if ($stock <= $reorderLevel) {
                return 'low_stock';
            }

            return 'in_stock';
        });
    }

    /**
     * Determine whether the product is below its reorder level.
     */
    protected function isLowStock(): Attribute
    {
        return Attribute::make(get: fn (): bool => $this->stock_status === 'low_stock');
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function inStock(Builder $query): Builder
    {
        return $query->whereColumn('current_stock', '>', 'reorder_level');
    }

    #[Scope]
    protected function lowStock(Builder $query): Builder
    {
        return $query
            ->where('current_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'reorder_level');
    }

    #[Scope]
    protected function outOfStock(Builder $query): Builder
    {
        return $query->where('current_stock', '<=', 0);
    }
}
