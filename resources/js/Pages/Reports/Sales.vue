<script setup>
import { computed, defineAsyncComponent, ref } from 'vue';
import { router } from '@inertiajs/vue3';
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
import FormInput from '../../Components/FormInput.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatCard from '../../Components/StatCard.vue';
import { useCurrency } from '../../Composables/useCurrency';

const VueApexCharts = defineAsyncComponent({
    loader: () => import('vue3-apexcharts'),
    loadingComponent: ChartPlaceholder,
    delay: 0,
});

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    filters: {
        type: Object,
        required: true,
    },
    stats: {
        type: Object,
        required: true,
    },
    sales_trend: {
        type: Array,
        default: () => [],
    },
    top_products: {
        type: Array,
        default: () => [],
    },
    top_customers: {
        type: Array,
        default: () => [],
    },
    payment_methods: {
        type: Array,
        default: () => [],
    },
    inventory: {
        type: Object,
        required: true,
    },
});

const { money } = useCurrency();

const fromDate = ref(props.filters.from_date);
const toDate = ref(props.filters.to_date);

const trendSeries = computed(() => [
    {
        name: 'Sales',
        data: props.sales_trend.map((month) => Number(month.sales)),
    },
    {
        name: 'Payments',
        data: props.sales_trend.map((month) => Number(month.payments)),
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
        categories: props.sales_trend.map((month) => month.label),
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

const methodSeries = computed(() => props.payment_methods.map((method) => Number(method.total)));

const methodChartOptions = computed(() => ({
    labels: props.payment_methods.map((method) => method.label),
    colors: ['#10b981', '#4f46e5', '#f59e0b', '#94a3b8'],
    legend: { position: 'bottom' },
    dataLabels: { enabled: true },
    tooltip: {
        y: {
            formatter: (value) => money(value),
        },
    },
}));

const productSeries = computed(() => [
    {
        name: 'Revenue',
        data: props.top_products.map((product) => Number(product.revenue)),
    },
]);

const productChartOptions = computed(() => ({
    chart: {
        type: 'bar',
        toolbar: { show: false },
        fontFamily: 'inherit',
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 4,
            barHeight: '60%',
        },
    },
    colors: ['#6366f1'],
    dataLabels: { enabled: false },
    xaxis: {
        categories: props.top_products.map((product) => product.name),
        labels: {
            formatter: (value) => money(value),
        },
    },
    tooltip: {
        x: {
            formatter: (_, { dataPointIndex }) => props.top_products[dataPointIndex]?.sku ?? '',
        },
        y: {
            formatter: (value) => money(value),
        },
    },
    grid: {
        borderColor: '#e5e7eb',
        strokeDashArray: 4,
    },
}));

const customerColumns = [
    { key: 'name', label: 'Customer' },
    { key: 'sales_count', label: 'Sales', align: 'right' },
    { key: 'revenue', label: 'Revenue', align: 'right' },
];

const lowStockColumns = [
    { key: 'name', label: 'Product' },
    { key: 'sku', label: 'SKU' },
    { key: 'current_stock', label: 'In stock', align: 'right' },
    { key: 'reorder_level', label: 'Reorder level', align: 'right' },
];

function applyFilters() {
    router.get(
        route('reports.sales'),
        {
            from_date: fromDate.value || undefined,
            to_date: toDate.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

function resetFilters() {
    fromDate.value = '';
    toDate.value = '';
    applyFilters();
}

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
            title="Reports"
            description="Sales performance, payment collection, and inventory health across the selected period."
        />

        <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <FormInput v-model="fromDate" type="date" label="From date" />
                <FormInput v-model="toDate" type="date" label="To date" :min="fromDate" />
                <div class="flex items-end">
                    <button
                        type="button"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                        @click="applyFilters"
                    >
                        Apply filters
                    </button>
                </div>
                <div class="flex items-end">
                    <button
                        type="button"
                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                        @click="resetFilters"
                    >
                        Reset to this year
                    </button>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Sales value" :value="money(stats.sales_total)" :icon="TrendingUp" tone="indigo" />
            <StatCard label="Payments received" :value="money(stats.payments_total)" :icon="Wallet" tone="emerald" />
            <StatCard label="Outstanding" :value="money(stats.outstanding_total)" :icon="Clock3" tone="rose" />
            <StatCard label="Sales recorded" :value="stats.sales_count" :icon="ShoppingCart" tone="sky" />
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm xl:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Sales and collections</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Monthly totals for the selected range</p>
                    </div>
                    <BarChart3 :size="18" class="text-gray-400" />
                </div>
                <VueApexCharts
                    type="area"
                    height="320"
                    :options="trendOptions"
                    :series="trendSeries"
                />
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Payment methods</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Collected value by method</p>
                    </div>
                    <CreditCard :size="18" class="text-gray-400" />
                </div>
                <VueApexCharts
                    type="donut"
                    height="320"
                    :options="methodChartOptions"
                    :series="methodSeries"
                />
            </div>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Top products</h2>
                        <p class="mt-0.5 text-xs text-gray-400">Highest revenue lines in the period</p>
                    </div>
                    <Package :size="18" class="text-gray-400" />
                </div>
                <VueApexCharts
                    type="bar"
                    height="320"
                    :options="productChartOptions"
                    :series="productSeries"
                />
            </div>

            <div class="xl:col-span-2">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900">Top customers</h2>
                    <span class="text-xs text-gray-400">By sales value</span>
                </div>
                <DataTable
                    :columns="customerColumns"
                    :rows="props.top_customers"
                    empty-message="No sales were recorded in this period."
                >
                    <template #cell-name="{ row }">
                        <span class="font-medium text-gray-900">{{ row.name }}</span>
                    </template>
                    <template #cell-sales_count="{ value }">
                        {{ value }}
                    </template>
                    <template #cell-revenue="{ value }">
                        <span class="font-medium text-gray-900">{{ money(value) }}</span>
                    </template>
                    <template #footer>
                        <div class="flex justify-end gap-8">
                            <span class="text-sm text-gray-500">Period sales</span>
                            <span class="text-base font-semibold text-gray-900">{{ money(stats.sales_total) }}</span>
                        </div>
                    </template>
                </DataTable>
            </div>
        </section>

        <section class="mt-8">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">Inventory health</h2>
                <span class="text-xs text-gray-400">Current catalogue snapshot</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <StatCard label="Active products" :value="inventory.active_products" :icon="Package" tone="indigo" />
                <StatCard label="In stock" :value="inventory.in_stock" :icon="ShoppingCart" tone="emerald" />
                <StatCard label="Low stock" :value="inventory.low_stock" :icon="Clock3" tone="amber" />
                <StatCard label="Out of stock" :value="inventory.out_of_stock" :icon="Package" tone="rose" />
                <StatCard label="Stock value" :value="money(inventory.stock_value)" :icon="Wallet" tone="sky" />
            </div>

            <div class="mt-4">
                <DataTable
                    :columns="lowStockColumns"
                    :rows="inventory.low_stock_products"
                    empty-message="No active products are below their reorder level."
                >
                    <template #cell-name="{ row }">
                        <span class="font-medium text-gray-900">{{ row.name }}</span>
                    </template>
                    <template #cell-current_stock="{ value }">
                        <span class="font-medium text-amber-700">{{ formatQuantity(value) }}</span>
                    </template>
                    <template #cell-reorder_level="{ value }">
                        {{ formatQuantity(value) }}
                    </template>
                </DataTable>
            </div>
        </section>
    </div>
</template>
