<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\PurchaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchases) {}

    /**
     * Display a paginated, filterable purchase list.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Purchase::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'supplier_id' => ['nullable', 'integer'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $supplierId = $filters['supplier_id'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        $purchases = Purchase::query()
            ->with(['supplier:id,name', 'createdBy:id,name'])
            ->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('invoice_number', "%{$search}%")
                        ->orWhereHas('supplier', fn (Builder $query) => $query->whereLike('name', "%{$search}%"));
                });
            })
            ->when(filled($supplierId), fn (Builder $query) => $query->where('supplier_id', $supplierId))
            ->when(filled($fromDate), fn (Builder $query) => $query->whereDate('purchase_date', '>=', $fromDate))
            ->when(filled($toDate), fn (Builder $query) => $query->whereDate('purchase_date', '<=', $toDate))
            ->latest('purchase_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases,
            'filters' => [
                'search' => $search,
                'supplier_id' => $supplierId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'stats' => [
                'total' => Purchase::query()->count(),
                'total_amount' => (string) (Purchase::query()->sum('total_amount') ?? '0.00'),
                'this_month' => Purchase::query()
                    ->whereMonth('purchase_date', now()->month)
                    ->whereYear('purchase_date', now()->year)
                    ->count(),
                'items' => PurchaseItem::query()->count(),
            ],
            'supplierOptions' => $this->supplierOptions(),
        ]);
    }

    /**
     * Show the form for recording a purchase.
     */
    public function create(): Response
    {
        Gate::authorize('create', Purchase::class);

        return Inertia::render('Purchases/Create', [
            'supplierOptions' => $this->supplierOptions(),
            'productOptions' => Product::query()
                ->active()
                ->with('unit:id,short_name')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'purchase_price', 'unit_id'])
                ->map(fn (Product $product) => [
                    'value' => $product->id,
                    'label' => "{$product->name} ({$product->sku})",
                    'price' => $product->purchase_price,
                    'unit' => $product->unit?->short_name,
                ])->all(),
        ]);
    }

    /**
     * Store a newly recorded purchase.
     */
    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $purchase = $this->purchases->create($request->validated(), $user);

        return to_route('purchases.show', $purchase)
            ->with('success', 'Purchase recorded successfully.');
    }

    /**
     * Display the given purchase.
     */
    public function show(Purchase $purchase): Response
    {
        Gate::authorize('view', $purchase);

        $purchase->load([
            'supplier',
            'createdBy:id,name',
            'items.product:id,name,sku',
            'stockMovements.product:id,name,sku',
            'stockMovements.createdBy:id,name',
        ]);

        return Inertia::render('Purchases/Show', [
            'purchase' => $purchase,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function supplierOptions(): array
    {
        return Supplier::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Supplier $supplier) => [
                'value' => $supplier->id,
                'label' => $supplier->name,
            ])->all();
    }
}
