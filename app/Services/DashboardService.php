<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class DashboardService
{
    public function __construct(private ReportService $reports) {}

    /**
     * Build the operational dashboard from current sales, payment, and stock data.
     *
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $salesToday = $this->saleTotals($today, $today);
        $salesThisMonth = $this->saleTotals($monthStart, $today);
        $paymentsThisMonth = $this->paymentTotals($monthStart, $today);

        $lifetimeSales = BigDecimal::of($this->decimal(Sale::query()->sum('total_amount')));
        $lifetimePayments = BigDecimal::of($this->decimal(Payment::query()->sum('amount')));
        $outstanding = $lifetimeSales->minus($lifetimePayments)->toScale(2, RoundingMode::HalfUp);

        $activeProducts = Product::query()->active()->count();
        $lowStock = Product::query()->active()->lowStock()->count();
        $outOfStock = Product::query()->active()->outOfStock()->count();

        return [
            'stats' => [
                'sales_today' => $salesToday['count'],
                'sales_today_total' => $salesToday['total'],
                'sales_this_month' => $salesThisMonth['count'],
                'sales_this_month_total' => $salesThisMonth['total'],
                'payments_this_month' => $paymentsThisMonth['count'],
                'payments_this_month_total' => $paymentsThisMonth['total'],
                'outstanding_total' => $outstanding->isLessThan(0) ? '0.00' : $outstanding->toString(),
                'active_products' => $activeProducts,
                'in_stock_products' => $activeProducts - $lowStock - $outOfStock,
                'low_stock_products' => $lowStock,
                'out_of_stock_products' => $outOfStock,
            ],
            'trend' => $this->reports->salesTrend(
                now()->subMonths(5)->startOfMonth()->toDateString(),
                $today,
            ),
            'low_stock_products' => Product::query()
                ->active()
                ->lowStock()
                ->orderBy('current_stock')
                ->limit(5)
                ->get(['id', 'name', 'sku', 'current_stock', 'reorder_level'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => BigDecimal::of($this->decimal($product->current_stock, 3))->toString(),
                    'reorder_level' => BigDecimal::of($this->decimal($product->reorder_level, 3))->toString(),
                    'stock_status' => $product->stock_status,
                ])->all(),
            'recent_sales' => Sale::query()
                ->with('customer:id,name')
                ->withSum('payments', 'amount')
                ->latest('sale_date')
                ->latest('id')
                ->limit(5)
                ->get(['id', 'invoice_number', 'customer_id', 'sale_date', 'status', 'total_amount'])
                ->map(fn (Sale $sale): array => [
                    'id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'customer_name' => $sale->customer?->name ?? '—',
                    'sale_date' => $sale->sale_date->toDateString(),
                    'status' => $sale->status,
                    'total_amount' => $this->decimal($sale->total_amount),
                    'paid_amount' => $sale->paid_amount,
                    'due_amount' => $sale->due_amount,
                    'payment_status' => $sale->payment_status,
                ])->all(),
            'recent_payments' => Payment::query()
                ->with(['sale:id,invoice_number,customer_id', 'sale.customer:id,name', 'createdBy:id,name'])
                ->latest('payment_date')
                ->latest('id')
                ->limit(5)
                ->get(['id', 'receipt_number', 'sale_id', 'payment_date', 'amount', 'method', 'created_by'])
                ->map(fn (Payment $payment): array => [
                    'id' => $payment->id,
                    'receipt_number' => $payment->receipt_number,
                    'invoice_number' => $payment->sale?->invoice_number ?? '—',
                    'customer_name' => $payment->sale?->customer?->name ?? '—',
                    'payment_date' => $payment->payment_date->toDateString(),
                    'amount' => $this->decimal($payment->amount),
                    'method' => $payment->method,
                    'method_label' => Payment::methodLabel($payment->method),
                    'recorded_by' => $payment->createdBy?->name ?? '—',
                ])->all(),
        ];
    }

    /**
     * @return array{count: int, total: string}
     */
    private function saleTotals(string $fromDate, string $toDate): array
    {
        $row = Sale::query()
            ->whereBetween('sale_date', [$fromDate, $toDate])
            ->toBase()
            ->selectRaw('COUNT(*) as record_count, COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();

        return [
            'count' => (int) $row->record_count,
            'total' => $this->decimal($row->total_amount),
        ];
    }

    /**
     * @return array{count: int, total: string}
     */
    private function paymentTotals(string $fromDate, string $toDate): array
    {
        $row = Payment::query()
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->toBase()
            ->selectRaw('COUNT(*) as record_count, COALESCE(SUM(amount), 0) as total_amount')
            ->first();

        return [
            'count' => (int) $row->record_count,
            'total' => $this->decimal($row->total_amount),
        ];
    }

    private function decimal(mixed $value, int $scale = 2): string
    {
        return BigDecimal::of((string) ($value ?? '0'))
            ->toScale($scale, RoundingMode::HalfUp)
            ->toString();
    }
}
