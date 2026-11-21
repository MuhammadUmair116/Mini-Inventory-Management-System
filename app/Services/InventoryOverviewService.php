<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\StockMovement;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class InventoryOverviewService
{
    public function __construct(private ReportService $reports) {}

    /**
     * Build the inventory overview from current stock levels and movement history.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function overview(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $movementSearch = trim((string) ($filters['movement_search'] ?? ''));

        return [
            'stats' => $this->reports->inventorySnapshot(),
            'products' => $this->products($filters, $search),
            'movements' => $this->movements($filters, $movementSearch),
            'ledger' => $this->ledgerStats($filters, $movementSearch),
            'filters' => [
                'search' => $search,
                'category_id' => (string) ($filters['category_id'] ?? ''),
                'stock_status' => (string) ($filters['stock_status'] ?? ''),
                'is_active' => (string) ($filters['is_active'] ?? ''),
                'movement_type' => (string) ($filters['movement_type'] ?? ''),
                'product_id' => (string) ($filters['product_id'] ?? ''),
                'movement_search' => $movementSearch,
                'from_date' => (string) ($filters['from_date'] ?? ''),
                'to_date' => (string) ($filters['to_date'] ?? ''),
            ],
            'categoryOptions' => $this->categoryOptions(),
            'productOptions' => $this->productOptions(),
            'movementTypeOptions' => $this->movementTypeOptions(),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function products(array $filters, string $search): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category:id,name', 'unit:id,name,short_name'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('sku', "%{$search}%")
                        ->orWhereLike('barcode', "%{$search}%");
                });
            })
            ->when(filled($filters['category_id'] ?? null), fn (Builder $query) => $query->where('category_id', $filters['category_id']))
            ->when(($filters['stock_status'] ?? null) === 'in_stock', fn (Builder $query) => $query->inStock())
            ->when(($filters['stock_status'] ?? null) === 'low_stock', fn (Builder $query) => $query->lowStock())
            ->when(($filters['stock_status'] ?? null) === 'out_of_stock', fn (Builder $query) => $query->outOfStock())
            ->when(
                in_array($filters['is_active'] ?? null, ['0', '1'], true),
                fn (Builder $query) => $query->where('is_active', (bool) $filters['is_active'])
            )
            ->latest('id')
            ->paginate(10, ['*'], 'products_page')
            ->withQueryString()
            ->through(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'category' => $product->category?->name ?? '—',
                'unit' => $product->unit?->short_name ?? '—',
                'purchase_price' => $this->decimal($product->purchase_price, 2),
                'current_stock' => $this->decimal($product->current_stock, 3),
                'reorder_level' => $this->decimal($product->reorder_level, 3),
                'stock_value' => BigDecimal::of($this->decimal($product->current_stock, 3))
                    ->multipliedBy(BigDecimal::of($this->decimal($product->purchase_price, 2)))
                    ->toScale(2, RoundingMode::HalfUp)
                    ->toString(),
                'stock_status' => $product->stock_status,
                'is_active' => $product->is_active,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function movements(array $filters, string $movementSearch): LengthAwarePaginator
    {
        return $this->movementQuery($filters, $movementSearch)
            ->with(['product:id,name,sku', 'createdBy:id,name', 'reference'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(10, ['*'], 'movements_page')
            ->withQueryString()
            ->through(function (StockMovement $movement): array {
                $reference = $movement->reference;

                return [
                    'id' => $movement->id,
                    'created_at' => $movement->created_at?->toDateString(),
                    'product_name' => $movement->product?->name ?? '—',
                    'product_sku' => $movement->product?->sku ?? '—',
                    'type' => $movement->type,
                    'direction' => in_array($movement->type, [StockMovement::TYPE_PURCHASE, StockMovement::TYPE_RETURN], true) ? 'in' : 'out',
                    'quantity' => $this->decimal($movement->quantity, 3),
                    'balance_after' => $this->decimal($movement->balance_after, 3),
                    'reference_kind' => $reference instanceof Sale ? 'sale' : ($reference instanceof Purchase ? 'purchase' : null),
                    'reference_id' => $reference?->getKey(),
                    'reference_label' => $reference?->invoice_number ?? '—',
                    'recorded_by' => $movement->createdBy?->name ?? '—',
                    'notes' => $movement->notes,
                ];
            });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{movement_count: int, inbound_quantity: string, outbound_quantity: string}
     */
    private function ledgerStats(array $filters, string $movementSearch): array
    {
        $row = $this->movementQuery($filters, $movementSearch)
            ->toBase()
            ->selectRaw("COUNT(*) as movement_count, COALESCE(SUM(CASE WHEN type IN ('purchase', 'return') THEN quantity ELSE 0 END), 0) as inbound_quantity, COALESCE(SUM(CASE WHEN type IN ('sale', 'adjustment') THEN quantity ELSE 0 END), 0) as outbound_quantity")
            ->first();

        return [
            'movement_count' => (int) $row->movement_count,
            'inbound_quantity' => $this->decimal($row->inbound_quantity, 3),
            'outbound_quantity' => $this->decimal($row->outbound_quantity, 3),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function movementQuery(array $filters, string $movementSearch): Builder
    {
        return StockMovement::query()
            ->when(filled($filters['movement_type'] ?? null), fn (Builder $query) => $query->ofType($filters['movement_type']))
            ->when(filled($filters['product_id'] ?? null), fn (Builder $query) => $query->where('product_id', $filters['product_id']))
            ->when($movementSearch !== '', function (Builder $query) use ($movementSearch) {
                $query->where(function (Builder $query) use ($movementSearch) {
                    $query->whereHas('product', function (Builder $query) use ($movementSearch) {
                        $query->whereLike('name', "%{$movementSearch}%")
                            ->orWhereLike('sku', "%{$movementSearch}%");
                    })->orWhereLike('notes', "%{$movementSearch}%");
                });
            })
            ->when(filled($filters['from_date'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from_date']))
            ->when(filled($filters['to_date'] ?? null), fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to_date']));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categoryOptions(): array
    {
        return Category::active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'value' => $category->id,
                'label' => $category->name,
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function productOptions(): array
    {
        return Product::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sku'])
            ->map(fn (Product $product) => [
                'value' => $product->id,
                'label' => "{$product->name} ({$product->sku})",
            ])->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function movementTypeOptions(): array
    {
        return [
            ['value' => StockMovement::TYPE_PURCHASE, 'label' => 'Purchase'],
            ['value' => StockMovement::TYPE_SALE, 'label' => 'Sale'],
            ['value' => StockMovement::TYPE_ADJUSTMENT, 'label' => 'Adjustment'],
            ['value' => StockMovement::TYPE_RETURN, 'label' => 'Return'],
        ];
    }

    private function decimal(mixed $value, int $scale): string
    {
        return BigDecimal::of((string) ($value ?? '0'))
            ->toScale($scale, RoundingMode::HalfUp)
            ->toString();
    }
}
