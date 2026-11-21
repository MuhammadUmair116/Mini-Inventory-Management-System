<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use App\Services\SaleService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SaleController extends Controller
{
    public function __construct(private SaleService $sales) {}

    /**
     * Display a paginated, filterable sale list.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Sale::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'customer_id' => ['nullable', 'integer'],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $customerId = $filters['customer_id'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        $sales = Sale::query()
            ->with(['customer:id,name', 'createdBy:id,name'])
            ->withCount('items')
            ->withSum('payments', 'amount')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->whereLike('invoice_number', "%{$search}%")
                        ->orWhereHas('customer', fn (Builder $query) => $query->whereLike('name', "%{$search}%"));
                });
            })
            ->when(filled($customerId), fn (Builder $query) => $query->where('customer_id', $customerId))
            ->when(filled($fromDate), fn (Builder $query) => $query->whereDate('sale_date', '>=', $fromDate))
            ->when(filled($toDate), fn (Builder $query) => $query->whereDate('sale_date', '<=', $toDate))
            ->latest('sale_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'filters' => [
                'search' => $search,
                'customer_id' => $customerId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'stats' => [
                'total' => Sale::query()->count(),
                'total_amount' => (string) (Sale::query()->sum('total_amount') ?? '0.00'),
                'discount_amount' => (string) (Sale::query()->sum('discount_amount') ?? '0.00'),
                'this_month' => Sale::query()
                    ->whereMonth('sale_date', now()->month)
                    ->whereYear('sale_date', now()->year)
                    ->count(),
                'items' => SaleItem::query()->count(),
                'outstanding_amount' => $this->decimal(
                    BigDecimal::of($this->decimal(Sale::query()->sum('total_amount')))
                        ->minus(BigDecimal::of($this->decimal(Payment::query()->sum('amount'))))
                        ->toScale(2, RoundingMode::HalfUp)
                        ->toString()
                ),
            ],
            'customerOptions' => $this->customerOptions(),
            'can' => [
                'create' => Gate::allows('create', Sale::class),
            ],
        ]);
    }

    /**
     * Show the form for recording a sale.
     */
    public function create(): Response
    {
        Gate::authorize('create', Sale::class);

        return Inertia::render('Sales/Create', [
            'customerOptions' => $this->customerOptions(),
            'productOptions' => Product::query()
                ->active()
                ->with('unit:id,short_name')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'sale_price', 'current_stock', 'unit_id'])
                ->map(fn (Product $product) => [
                    'value' => $product->id,
                    'label' => "{$product->name} ({$product->sku})",
                    'price' => $product->sale_price,
                    'unit' => $product->unit?->short_name,
                    'stock' => (string) $product->current_stock,
                ])->all(),
        ]);
    }

    /**
     * Store a newly recorded sale.
     */
    public function store(StoreSaleRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $sale = $this->sales->create($request->validated(), $user);
        } catch (InsufficientStockException $exception) {
            return to_route('sales.create')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return to_route('sales.show', $sale)
            ->with('success', 'Sale recorded successfully.');
    }

    /**
     * Display the given sale.
     */
    public function show(Sale $sale): Response
    {
        Gate::authorize('view', $sale);

        $sale->loadSum('payments', 'amount')->load([
            'customer',
            'createdBy:id,name',
            'items.product:id,name,sku',
            'payments.createdBy:id,name',
            'stockMovements.product:id,name,sku',
            'stockMovements.createdBy:id,name',
        ]);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function customerOptions(): array
    {
        return Customer::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Customer $customer) => [
                'value' => $customer->id,
                'label' => $customer->name,
            ])->all();
    }

    private function decimal(mixed $value): string
    {
        return BigDecimal::of((string) ($value ?? '0'))
            ->toScale(2, RoundingMode::HalfUp)
            ->toString();
    }
}
