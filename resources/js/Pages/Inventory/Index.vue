<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowUpRight,
    Boxes,
    CheckCircle2,
    CircleAlert,
    ClipboardList,
    Package,
    Wallet,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import FormInput from '../../Components/FormInput.vue';
import FormSelect from '../../Components/FormSelect.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import SearchInput from '../../Components/SearchInput.vue';
import StatCard from '../../Components/StatCard.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useCurrency } from '../../Composables/useCurrency';
import { useDebounce } from '../../Composables/useDebounce';
import { formatDate } from '../../Utils/formatDate';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    products: {
        type: Object,
        required: true,
    },
    movements: {
        type: Object,
        required: true,
    },
    ledger: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    categoryOptions: {
        type: Array,
        default: () => [],
    },
    productOptions: {
        type: Array,
        default: () => [],
    },
    movementTypeOptions: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: Object,
        default: () => ({ viewPurchases: false }),
    },
});

const { money } = useCurrency();

const productRows = computed(() => props.products.data);
const movementRows = computed(() => props.movements.data);

const search = ref(props.filters.search ?? '');
const categoryId = ref(String(props.filters.category_id ?? ''));
const stockStatus = ref(props.filters.stock_status ?? '');
const isActive = ref(String(props.filters.is_active ?? ''));
const movementType = ref(props.filters.movement_type ?? '');
const productId = ref(String(props.filters.product_id ?? ''));
const movementSearch = ref(props.filters.movement_search ?? '');
const fromDate = ref(props.filters.from_date ?? '');
const toDate = ref(props.filters.to_date ?? '');
const today = new Date().toISOString().slice(0, 10);

const productColumns = [
    { key: 'name', label: 'Product' },
    { key: 'category', label: 'Category' },
    { key: 'unit', label: 'Unit' },
    { key: 'current_stock', label: 'On hand', align: 'right' },
    { key: 'reorder_level', label: 'Reorder at', align: 'right' },
    { key: 'stock_value', label: 'Stock value', align: 'right' },
    { key: 'stock_status', label: 'Status' },
];

const movementColumns = [
    { key: 'created_at', label: 'Date' },
    { key: 'product_name', label: 'Product' },
    { key: 'type', label: 'Type' },
    { key: 'quantity', label: 'Quantity', align: 'right' },
    { key: 'balance_after', label: 'Balance after', align: 'right' },
    { key: 'reference_label', label: 'Reference' },
    { key: 'recorded_by', label: 'Recorded by' },
    { key: 'notes', label: 'Notes' },
];

const stockStatusOptions = [
    { value: 'in_stock', label: 'In stock' },
    { value: 'low_stock', label: 'Low stock' },
    { value: 'out_of_stock', label: 'Out of stock' },
];

const activeOptions = [
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

function formatQuantity(value) {
    return Number(value ?? 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
    });
}

function signedQuantity(row) {
    return `${row.direction === 'in' ? '+' : '−'}${formatQuantity(row.quantity)}`;
}

function referenceRoute(row) {
    if (row.reference_kind === 'sale') {
        return route('sales.show', row.reference_id);
    }

    if (row.reference_kind === 'purchase' && props.permissions.viewPurchases) {
        return route('purchases.show', row.reference_id);
    }

    return null;
}

const applyFilters = useDebounce(() => {
    router.get(
        route('inventory.index'),
        {
            search: search.value,
            category_id: categoryId.value || undefined,
            stock_status: stockStatus.value || undefined,
            is_active: isActive.value || undefined,
            movement_type: movementType.value || undefined,
            product_id: productId.value || undefined,
            movement_search: movementSearch.value || undefined,
            from_date: fromDate.value || undefined,
            to_date: toDate.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}, 300);

watch(
    [search, categoryId, stockStatus, isActive, movementType, productId, movementSearch, fromDate, toDate],
    () => applyFilters(),
);

function resetProductFilters() {
    search.value = '';
    categoryId.value = '';
    stockStatus.value = '';
    isActive.value = '';
}

function resetMovementFilters() {
    movementType.value = '';
    productId.value = '';
    movementSearch.value = '';
    fromDate.value = '';
    toDate.value = '';
}
</script>

<template>
    <div>
        <PageHeader
            title="Inventory"
            description="Current stock levels and the movement ledger that produced them."
        />

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <StatCard
                label="Active products"
                :value="stats.active_products"
                :icon="Boxes"
                tone="indigo"
            />
            <StatCard
                label="In stock"
                :value="stats.in_stock"
                :icon="CheckCircle2"
                tone="emerald"
            />
            <StatCard
                label="Low stock"
                :value="stats.low_stock"
                :icon="CircleAlert"
                tone="amber"
            />
            <StatCard
                label="Out of stock"
                :value="stats.out_of_stock"
                :icon="Package"
                tone="rose"
            />
            <StatCard
                label="Stock value"
                :value="money(stats.stock_value)"
                :icon="Wallet"
                hint="Active stock at purchase price"
                tone="sky"
            />
        </section>

        <section class="mt-8">
            <h2 class="text-sm font-semibold text-gray-900">Stock levels</h2>
            <p class="mt-0.5 text-xs text-gray-400">
                Quantities are updated only by recorded purchase and sale movements.
            </p>

            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <SearchInput v-model="search" placeholder="Search name, SKU, barcode…" />
                <FormSelect
                    v-model="categoryId"
                    :options="categoryOptions"
                    placeholder="All categories"
                />
                <FormSelect
                    v-model="stockStatus"
                    :options="stockStatusOptions"
                    placeholder="All stock levels"
                />
                <FormSelect
                    v-model="isActive"
                    :options="activeOptions"
                    placeholder="All statuses"
                />
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                    @click="resetProductFilters"
                >
                    Reset filters
                </button>
            </div>

            <div class="mt-4">
                <DataTable
                    :columns="productColumns"
                    :rows="productRows"
                    empty-message="No products match the selected stock filters."
                >
                    <template #cell-name="{ row }">
                        <div>
                            <span class="font-medium text-gray-900">{{ row.name }}</span>
                            <span class="mt-0.5 block text-xs text-gray-400">
                                {{ row.sku }}
                                <span v-if="!row.is_active" class="ml-1 text-rose-500">· Inactive</span>
                            </span>
                        </div>
                    </template>
                    <template #cell-current_stock="{ value }">
                        <span class="font-medium text-gray-900">{{ formatQuantity(value) }}</span>
                    </template>
                    <template #cell-reorder_level="{ value }">
                        {{ formatQuantity(value) }}
                    </template>
                    <template #cell-stock_value="{ value }">
                        <span class="font-medium text-gray-900">{{ money(value) }}</span>
                    </template>
                    <template #cell-stock_status="{ value }">
                        <StatusBadge :status="value" />
                    </template>
                </DataTable>

                <Pagination :paginator="props.products" />
            </div>
        </section>

        <section class="mt-10">
            <h2 class="text-sm font-semibold text-gray-900">Stock movement ledger</h2>
            <p class="mt-0.5 text-xs text-gray-400">
                Every quantity change recorded by purchases and sales, with the resulting balance.
            </p>

            <div class="mt-3 grid gap-4 sm:grid-cols-3">
                <StatCard
                    label="Movements"
                    :value="ledger.movement_count"
                    :icon="ClipboardList"
                    hint="Matching the current filters"
                    tone="indigo"
                />
                <StatCard
                    label="Quantity received"
                    :value="formatQuantity(ledger.inbound_quantity)"
                    :icon="ArrowDownRight"
                    hint="Purchases and returns"
                    tone="emerald"
                />
                <StatCard
                    label="Quantity issued"
                    :value="formatQuantity(ledger.outbound_quantity)"
                    :icon="ArrowUpRight"
                    hint="Sales and adjustments"
                    tone="rose"
                />
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
                <SearchInput
                    v-model="movementSearch"
                    placeholder="Search product or notes…"
                />
                <FormSelect
                    v-model="movementType"
                    :options="props.movementTypeOptions"
                    placeholder="All movement types"
                />
                <FormSelect
                    v-model="productId"
                    :options="props.productOptions"
                    placeholder="All products"
                />
                <FormInput v-model="fromDate" type="date" :max="today" aria-label="From date" />
                <FormInput v-model="toDate" type="date" :max="today" aria-label="To date" />
                <button
                    type="button"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                    @click="resetMovementFilters"
                >
                    Reset filters
                </button>
            </div>

            <div class="mt-4">
                <DataTable
                    :columns="movementColumns"
                    :rows="movementRows"
                    empty-message="No stock movements match the selected filters."
                >
                    <template #cell-created_at="{ value }">
                        {{ formatDate(value) }}
                    </template>
                    <template #cell-product_name="{ row }">
                        <div>
                            <span class="font-medium text-gray-900">{{ row.product_name }}</span>
                            <span class="mt-0.5 block text-xs text-gray-400">{{ row.product_sku }}</span>
                        </div>
                    </template>
                    <template #cell-type="{ row }">
                        <span
                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ring-1 ring-inset"
                            :class="row.direction === 'in'
                                ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20'
                                : 'bg-rose-50 text-rose-700 ring-rose-600/20'"
                        >
                            {{ row.type }}
                        </span>
                    </template>
                    <template #cell-quantity="{ row }">
                        <span
                            class="font-medium"
                            :class="row.direction === 'in' ? 'text-emerald-700' : 'text-rose-700'"
                        >
                            {{ signedQuantity(row) }}
                        </span>
                    </template>
                    <template #cell-balance_after="{ value }">
                        {{ formatQuantity(value) }}
                    </template>
                    <template #cell-reference_label="{ row }">
                        <Link
                            v-if="referenceRoute(row)"
                            :href="referenceRoute(row)"
                            class="font-medium text-indigo-600 hover:text-indigo-700"
                        >
                            {{ row.reference_label }}
                        </Link>
                        <span v-else class="text-gray-700">{{ row.reference_label }}</span>
                        <span class="ml-1 text-xs capitalize text-gray-400">
                            {{ row.reference_kind ?? row.type }}
                        </span>
                    </template>
                    <template #cell-notes="{ value }">
                        <span class="text-gray-500">{{ value ?? '—' }}</span>
                    </template>
                </DataTable>

                <Pagination :paginator="props.movements" />
            </div>
        </section>
    </div>
</template>
