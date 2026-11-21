<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\StockMovement;
use App\Services\InventoryOverviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function __construct(private InventoryOverviewService $inventory) {}

    /**
     * Display current stock levels and the stock movement ledger.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('view-inventory');

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'stock_status' => ['nullable', Rule::in(['in_stock', 'low_stock', 'out_of_stock'])],
            'is_active' => ['nullable', Rule::in(['0', '1'])],
            'movement_type' => ['nullable', Rule::in([
                StockMovement::TYPE_PURCHASE,
                StockMovement::TYPE_SALE,
                StockMovement::TYPE_ADJUSTMENT,
                StockMovement::TYPE_RETURN,
            ])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'movement_search' => ['nullable', 'string', 'max:100'],
            'from_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to_date' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
                'after_or_equal:from_date',
            ],
        ]);

        return Inertia::render('Inventory/Index', [
            ...$this->inventory->overview($filters),
            'permissions' => [
                'viewPurchases' => Gate::allows('viewAny', Purchase::class),
            ],
        ]);
    }
}
