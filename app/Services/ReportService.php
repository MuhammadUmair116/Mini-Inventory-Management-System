<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * Build the sales, payment, and inventory report for the given date range.
     *
     * @return array<string, mixed>
     */
    public function salesOverview(string $fromDate, string $toDate): array
    {
        $salesTotal = $this->amount(Sale::query()->whereBetween('sale_date', [$fromDate, $toDate])->sum('total_amount'));
        $discountTotal = $this->amount(Sale::query()->whereBetween('sale_date', [$fromDate, $toDate])->sum('discount_amount'));
        $paymentsTotal = $this->amount(Payment::query()->whereBetween('payment_date', [$fromDate, $toDate])->sum('amount'));
        $salesCount = Sale::query()->whereBetween('sale_date', [$fromDate, $toDate])->count();
        $paymentCount = Payment::query()->whereBetween('payment_date', [$fromDate, $toDate])->count();

        $lifetimeSales = BigDecimal::of($this->amount(Sale::query()->sum('total_amount')));
        $lifetimePayments = BigDecimal::of($this->amount(Payment::query()->sum('amount')));
        $outstanding = $lifetimeSales->minus($lifetimePayments)->toScale(2, RoundingMode::HalfUp);

        return [
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
            ],
            'stats' => [
                'sales_total' => $salesTotal,
                'discount_total' => $discountTotal,
                'payments_total' => $paymentsTotal,
                'outstanding_total' => $outstanding->isLessThan(0) ? '0.00' : $outstanding->toString(),
                'sales_count' => $salesCount,
                'payment_count' => $paymentCount,
                'average_sale' => $salesCount > 0
                    ? BigDecimal::of($salesTotal)->dividedBy($salesCount, 2, RoundingMode::HalfUp)->toString()
                    : '0.00',
            ],
            'sales_trend' => $this->salesTrend($fromDate, $toDate),
            'top_products' => $this->topProducts($fromDate, $toDate),
            'top_customers' => $this->topCustomers($fromDate, $toDate),
            'payment_methods' => $this->paymentMethods($fromDate, $toDate),
            'inventory' => $this->inventorySnapshot(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function salesTrend(string $fromDate, string $toDate): array
    {
        $months = $this->monthKeys($fromDate, $toDate);

        $sales = Sale::query()
            ->selectRaw("DATE_FORMAT(sale_date, '%Y-%m') as period, SUM(total_amount) as total, COUNT(*) as sales_count")
            ->whereBetween('sale_date', [$fromDate, $toDate])
            ->groupByRaw("DATE_FORMAT(sale_date, '%Y-%m')")
            ->get()
            ->keyBy('period');

        $payments = Payment::query()
            ->selectRaw("DATE_FORMAT(payment_date, '%Y-%m') as period, SUM(amount) as total")
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->groupByRaw("DATE_FORMAT(payment_date, '%Y-%m')")
            ->get()
            ->keyBy('period');

        foreach ($months as $key => $month) {
            $months[$key]['sales'] = $this->amount($sales->get($key)?->total);
            $months[$key]['payments'] = $this->amount($payments->get($key)?->total);
            $months[$key]['sales_count'] = (int) ($sales->get($key)?->sales_count ?? 0);
        }

        return array_values($months);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function monthKeys(string $fromDate, string $toDate): array
    {
        $start = Carbon::parse($fromDate)->startOfMonth();
        $end = Carbon::parse($toDate)->startOfMonth();
        $months = [];

        while ($start->lte($end)) {
            $key = $start->format('Y-m');

            $months[$key] = [
                'period' => $key,
                'label' => $start->format('M Y'),
                'sales' => '0.00',
                'payments' => '0.00',
                'sales_count' => 0,
            ];

            $start->addMonth();
        }

        return $months;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topProducts(string $fromDate, string $toDate): array
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.sale_date', [$fromDate, $toDate])
            ->select(['products.id', 'products.name', 'products.sku'])
            ->selectRaw('SUM(sale_items.quantity) as quantity_sold')
            ->selectRaw('SUM(sale_items.line_total) as revenue')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn (SaleItem $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'quantity' => BigDecimal::of($this->amount($item->quantity_sold, 3))->toString(),
                'revenue' => $this->amount($item->revenue),
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topCustomers(string $fromDate, string $toDate): array
    {
        return Sale::query()
            ->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->whereBetween('sales.sale_date', [$fromDate, $toDate])
            ->select(['customers.id', 'customers.name'])
            ->selectRaw('COUNT(sales.id) as sales_count')
            ->selectRaw('SUM(sales.total_amount) as revenue')
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get()
            ->map(fn (Sale $sale): array => [
                'id' => $sale->id,
                'name' => $sale->name,
                'sales_count' => (int) $sale->sales_count,
                'revenue' => $this->amount($sale->revenue),
            ])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function paymentMethods(string $fromDate, string $toDate): array
    {
        $rows = Payment::query()
            ->whereBetween('payment_date', [$fromDate, $toDate])
            ->select('method')
            ->selectRaw('COUNT(*) as payment_count')
            ->selectRaw('SUM(amount) as total')
            ->groupBy('method')
            ->get()
            ->keyBy('method');

        $methods = [];

        foreach (array_keys(Payment::methods()) as $method) {
            $row = $rows->get($method);

            $methods[] = [
                'method' => $method,
                'label' => Payment::methodLabel($method),
                'payment_count' => (int) ($row?->payment_count ?? 0),
                'total' => $this->amount($row?->total),
            ];
        }

        return $methods;
    }

    /**
     * @return array<string, mixed>
     */
    public function inventorySnapshot(): array
    {
        $products = Product::query()->active()->get(['current_stock', 'reorder_level', 'purchase_price']);
        $stockValue = BigDecimal::zero();
        $inStock = 0;
        $lowStock = 0;
        $outOfStock = 0;

        foreach ($products as $product) {
            $stock = BigDecimal::of($this->amount($product->current_stock, 3));
            $stockValue = $stockValue->plus(
                $stock->multipliedBy(BigDecimal::of($this->amount($product->purchase_price)))
                    ->toScale(2, RoundingMode::HalfUp)
            )->toScale(2);

            if ($stock->isLessThanOrEqualTo(0)) {
                $outOfStock++;
            } elseif ($stock->isLessThanOrEqualTo(BigDecimal::of($this->amount($product->reorder_level, 3)))) {
                $lowStock++;
            } else {
                $inStock++;
            }
        }

        $lowStockProducts = Product::query()
            ->active()
            ->lowStock()
            ->orderBy('current_stock')
            ->limit(5)
            ->get(['id', 'name', 'sku', 'current_stock', 'reorder_level'])
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'current_stock' => BigDecimal::of($this->amount($product->current_stock, 3))->toString(),
                'reorder_level' => BigDecimal::of($this->amount($product->reorder_level, 3))->toString(),
            ])->all();

        return [
            'active_products' => $products->count(),
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'stock_value' => $stockValue->toString(),
            'low_stock_products' => $lowStockProducts,
        ];
    }

    private function amount(mixed $value, int $scale = 2): string
    {
        return BigDecimal::of((string) ($value ?? '0'))
            ->toScale($scale, RoundingMode::HalfUp)
            ->toString();
    }
}
