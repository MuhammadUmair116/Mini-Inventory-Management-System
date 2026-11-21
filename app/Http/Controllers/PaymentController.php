<?php

namespace App\Http\Controllers;

use App\Exceptions\OverpaymentException;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use App\Services\PaymentService;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    /**
     * Display a paginated, filterable payment list.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Payment::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'sale_id' => ['nullable', 'integer'],
            'method' => ['nullable', 'string', Rule::in(array_keys(Payment::methods()))],
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $saleId = $filters['sale_id'] ?? null;
        $method = $filters['method'] ?? null;
        $fromDate = $filters['from_date'] ?? null;
        $toDate = $filters['to_date'] ?? null;

        $query = Payment::query()
            ->with(['sale.customer:id,name', 'createdBy:id,name']);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search) {
                $query->whereLike('receipt_number', "%{$search}%")
                    ->orWhereLike('reference', "%{$search}%")
                    ->orWhereHas('sale', function (Builder $query) use ($search) {
                        $query->whereLike('invoice_number', "%{$search}%")
                            ->orWhereHas('customer', fn (Builder $query) => $query->whereLike('name', "%{$search}%"));
                    });
            });
        }

        $payments = $query
            ->when(filled($saleId), fn (Builder $query) => $query->where('sale_id', $saleId))
            ->when(filled($method), fn (Builder $query) => $query->where('method', $method))
            ->when(filled($fromDate), fn (Builder $query) => $query->whereDate('payment_date', '>=', $fromDate))
            ->when(filled($toDate), fn (Builder $query) => $query->whereDate('payment_date', '<=', $toDate))
            ->latest('payment_date')
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        $salesTotal = BigDecimal::of($this->decimal(Sale::query()->sum('total_amount')));
        $paymentsTotal = BigDecimal::of($this->decimal(Payment::query()->sum('amount')));
        $outstanding = $salesTotal->minus($paymentsTotal)->toScale(2, RoundingMode::HalfUp);

        return Inertia::render('Payments/Index', [
            'payments' => $payments,
            'filters' => [
                'search' => $search,
                'sale_id' => $saleId,
                'method' => $method,
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'stats' => [
                'total_collected' => $paymentsTotal->toString(),
                'this_month_collected' => $this->decimal(Payment::query()
                    ->whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->sum('amount')),
                'outstanding_total' => $outstanding->isLessThan(0) ? '0.00' : $outstanding->toString(),
                'payment_count' => Payment::query()->count(),
            ],
            'saleOptions' => $this->saleOptions(),
            'methodOptions' => collect(Payment::methods())
                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
            'can' => [
                'create' => Gate::allows('create', Payment::class),
            ],
        ]);
    }

    /**
     * Store a newly recorded payment.
     */
    public function store(StorePaymentRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $this->payments->create($request->validated(), $user);
        } catch (OverpaymentException $exception) {
            return to_route('payments.index')
                ->withInput()
                ->with('error', $exception->getMessage());
        }

        return to_route('payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function saleOptions(): array
    {
        return Sale::query()
            ->with('customer:id,name')
            ->withSum('payments', 'amount')
            ->orderBy('invoice_number')
            ->get(['id', 'invoice_number', 'customer_id', 'total_amount'])
            ->map(function (Sale $sale): array {
                $paid = BigDecimal::of($this->decimal($sale->payments_sum_amount));
                $due = BigDecimal::of($this->decimal($sale->total_amount))
                    ->minus($paid)
                    ->toScale(2, RoundingMode::HalfUp);

                return [
                    'value' => $sale->id,
                    'label' => "{$sale->invoice_number} — {$sale->customer?->name}",
                    'due' => $due->isLessThan(0) ? '0.00' : $due->toString(),
                ];
            })
            ->filter(fn (array $option): bool => BigDecimal::of($option['due'])->isGreaterThan(0))
            ->values()
            ->all();
    }

    private function decimal(mixed $value): string
    {
        return BigDecimal::of((string) ($value ?? '0'))
            ->toScale(2, RoundingMode::HalfUp)
            ->toString();
    }
}
