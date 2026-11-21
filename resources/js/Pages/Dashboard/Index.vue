<script setup>
import { computed, defineAsyncComponent } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    Clock3,
    CreditCard,
    Package,
    ShoppingCart,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ChartPlaceholder from '../../Components/ChartPlaceholder.vue';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useCurrency } from '../../Composables/useCurrency';
import { formatDate } from '../../Utils/formatDate';

const VueApexCharts = defineAsyncComponent({
    loader: () => import('vue3-apexcharts'),
    loadingComponent: ChartPlaceholder,
    delay: 0,
});

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    trend: {
        type: Array,
        default: () => [],
    },
    low_stock_products: {
        type: Array,
        default: () => [],
    },
    recent_sales: {
        type: Array,
        default: () => [],
    },
    recent_payments: {
        type: Array,
        default: () => [],
    },
});

const { money } = useCurrency();
const page = usePage();
const currentUser = computed(() => page.props.auth?.user);

const trendSeries = computed(() => [
    {
        name: 'Sales',
        data: props.trend.map((month) => Number(month.sales)),
    },
    {
        name: 'Payments',
        data: props.trend.map((month) => Number(month.payments)),
    },
]);

const trendOptions = computed(() => ({
    chart: {
        type: 'area',
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    colors: ['#4f46e5', '#10b981'],
    dataLabels: { enabled: false },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
        categories: props.trend.map((month) => month.label),
    },
    yaxis: {
        labels: {
            formatter: (value) => money(value),
        },
    },
    tooltip: {
        y: {
            formatter: (value) => money(value),
        },
    },
    legend: {
        position: 'top',
        horizontalAlign: 'right',
    },
    grid: {
        borderColor: '#e5e7eb',
        strokeDashArray: 4,
    },
}));

const stockSeries = computed(() => [
    props.stats.in_stock_products,
    props.stats.low_stock_products,
    props.stats.out_of_stock_products,
]);

const stockOptions = computed(() => ({
    labels: ['In stock', 'Low stock', 'Out of stock'],
    colors: ['#10b981', '#f59e0b', '#e11d48'],
    legend: { position: 'bottom' },
    dataLabels: { enabled: true },
    tooltip: {
        y: {
            formatter: (value) => `${value} products`,
        },
    },
}));

const saleColumns = [
    { key: 'invoice_number', label: 'Invoice' },
    { key: 'customer_name', label: 'Customer' },
    { key: 'sale_date', label: 'Date' },
    { key: 'total_amount', label: 'Total', align: 'right' },
    { key: 'due_amount', label: 'Due', align: 'right' },
    { key: 'payment_status', label: 'Payment' },
];

const paymentColumns = [
    { key: 'receipt_number', label: 'Receipt' },
    { key: 'invoice_number', label: 'Sale' },
    { key: 'payment_date', label: 'Date' },
    { key: 'method_label', label: 'Method' },
    { key: 'amount', label: 'Amount', align: 'right' },
];

const lowStockColumns = [
    { key: 'name', label: 'Product' },
    { key: 'sku', label: 'SKU' },
    { key: 'current_stock', label: 'In stock', align: 'right' },
    { key: 'reorder_level', label: 'Reorder level', align: 'right' },
    { key: 'stock_status', label: 'Status' },
];

function formatQuantity(value) {
    return Number(value ?? 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
    });
}
</script>

<template>
    <div>
        <PageHeader
            title="Dashboard"
            :description="`Welcome back, ${currentUser?.name ?? 'there'}. Here is your current operational overview.`"
        />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard
                label="Sales this month"
                :value="money(stats.sales_this_month_total)"
                :icon="TrendingUp"
                :hint="`${stats.sales_this_month} orders · ${stats.sales_today} today`"
                tone="indigo"
            />
            <StatCard
                label="Payments collected"
                :value="money(stats.payments_this_month_total)"
                :icon="Wallet"
                :hint="`${stats.payments_this_month} receipts this month`"
                tone="emerald"
            />
            <StatCard
                label="Outstanding"
                :value="money(stats.outstanding_total)"
                :icon="Clock3"
                hint="Unpaid balance across all sales"
                tone="rose"
            />
            <StatCard
                label="Needs attention"
                :value="stats.low_stock_products + stats.out_of_stock_products"
                :icon="Package"
                :hint="`${stats.out_of_stock_products} out of stock`"
                tone="amber"
            />
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-3">
            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Sales and collections</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Monthly totals for the last six months</p>
                    </div>
                    <BarChart3 :size="18" class="text-gray-400" />
                </div>
                <VueApexCharts
                    type="area"
                    height="300"
                    :options="trendOptions"
                    :series="trendSeries"
                />
            </div>

            <div class="min-w-0 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Stock health</h2>
                        <p class="mt-0.5 text-xs text-gray-400">{{ stats.active_products }} active products</p>
                    </div>
                    <Package :size="18" class="text-gray-400" />
                </div>
                <VueApexCharts
                    type="donut"
                    height="300"
                    :options="stockOptions"
                    :series="stockSeries"
                />
            </div>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="min-w-0">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Recent sales</h2>
                    <Link
                        :href="route('sales.index')"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700"
                    >
                        View all
                    </Link>
                </div>
                <DataTable
                    :columns="saleColumns"
                    :rows="props.recent_sales"
                    empty-message="No sales have been recorded yet."
                >
                    <template #cell-invoice_number="{ row }">
                        <Link
                            :href="route('sales.show', row.id)"
                            class="font-medium text-indigo-600 hover:text-indigo-700"
                        >
                            {{ row.invoice_number }}
                        </Link>
                    </template>
                    <template #cell-customer_name="{ value }">
                        {{ value }}
                    </template>
                    <template #cell-sale_date="{ value }">
                        {{ formatDate(value) }}
                    </template>
                    <template #cell-total_amount="{ value }">
                        <span class="font-medium text-gray-900">{{ money(value) }}</span>
                    </template>
                    <template #cell-due_amount="{ value }">
                        <span :class="Number(value) > 0 ? 'font-medium text-rose-700' : 'text-gray-400'">
                            {{ money(value) }}
                        </span>
                    </template>
                    <template #cell-payment_status="{ value }">
                        <StatusBadge :status="value" />
                    </template>
                </DataTable>
            </div>

            <div class="min-w-0">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Recent payments</h2>
                    <Link
                        :href="route('payments.index')"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700"
                    >
                        View all
                    </Link>
                </div>
                <DataTable
                    :columns="paymentColumns"
                    :rows="props.recent_payments"
                    empty-message="No payments have been recorded yet."
                >
                    <template #cell-receipt_number="{ value }">
                        <span class="font-medium text-gray-900">{{ value }}</span>
                    </template>
                    <template #cell-invoice_number="{ value }">
                        {{ value }}
                    </template>
                    <template #cell-payment_date="{ value }">
                        {{ formatDate(value) }}
                    </template>
                    <template #cell-method_label="{ value }">
                        <span class="capitalize">{{ value }}</span>
                    </template>
                    <template #cell-amount="{ value }">
                        <span class="font-medium text-emerald-700">{{ money(value) }}</span>
                    </template>
                </DataTable>
            </div>
        </section>

        <section class="mt-8">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Low stock alerts</h2>
                <Link
                    :href="route('inventory.index')"
                    class="text-xs font-medium text-indigo-600 hover:text-indigo-700"
                >
                    Open inventory
                </Link>
            </div>
            <DataTable
                :columns="lowStockColumns"
                :rows="props.low_stock_products"
                empty-message="No active products are below their reorder level."
            >
                <template #cell-name="{ value }">
                    <span class="font-medium text-gray-900">{{ value }}</span>
                </template>
                <template #cell-current_stock="{ value }">
                    <span class="font-medium text-amber-700">{{ formatQuantity(value) }}</span>
                </template>
                <template #cell-reorder_level="{ value }">
                    {{ formatQuantity(value) }}
                </template>
                <template #cell-stock_status="{ value }">
                    <StatusBadge :status="value" />
                </template>
            </DataTable>
        </section>
    </div>
</template>
