<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Banknote, CalendarDays, Clock3, Eye, Percent, Plus, ShoppingCart, Wallet } from '@lucide/vue';
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
    sales: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', customer_id: '', from_date: '', to_date: '' }),
    },
    stats: {
        type: Object,
        required: true,
    },
    customerOptions: {
        type: Array,
        default: () => [],
    },
    can: {
        type: Object,
        default: () => ({ create: false }),
    },
});

const { money } = useCurrency();

const columns = [
    { key: 'invoice_number', label: 'Invoice' },
    { key: 'customer', label: 'Customer' },
    { key: 'sale_date', label: 'Sale date' },
    { key: 'items_count', label: 'Items', align: 'right' },
    { key: 'discount_amount', label: 'Discount', align: 'right' },
    { key: 'total_amount', label: 'Total', align: 'right' },
    { key: 'paid_amount', label: 'Paid', align: 'right' },
    { key: 'due_amount', label: 'Due', align: 'right' },
    { key: 'payment_status', label: 'Payment' },
    { key: 'created_by', label: 'Recorded by' },
    { key: 'created_at', label: 'Created' },
];

const rows = computed(() => props.sales.data);
const search = ref(props.filters.search ?? '');
const customerId = ref(String(props.filters.customer_id ?? ''));
const fromDate = ref(props.filters.from_date ?? '');
const toDate = ref(props.filters.to_date ?? '');

const applyFilters = useDebounce(() => {
    router.get(
        route('sales.index'),
        {
            search: search.value,
            customer_id: customerId.value || undefined,
            from_date: fromDate.value || undefined,
            to_date: toDate.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}, 300);

watch([search, customerId, fromDate, toDate], () => applyFilters());

function resetFilters() {
    search.value = '';
    customerId.value = '';
    fromDate.value = '';
    toDate.value = '';
}
</script>

<template>
    <div>
        <PageHeader
            title="Sales"
            description="Record customer orders and track the stock movements they create."
        >
            <template #actions>
                <Link
                    v-if="can.create"
                    :href="route('sales.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    <Plus :size="16" />
                    New sale
                </Link>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <StatCard label="Total sales" :value="stats.total" :icon="ShoppingCart" tone="indigo" />
            <StatCard label="Sales value" :value="money(stats.total_amount)" :icon="Banknote" tone="emerald" />
            <StatCard label="Discount given" :value="money(stats.discount_amount)" :icon="Percent" tone="amber" />
            <StatCard label="This month" :value="stats.this_month" :icon="CalendarDays" tone="sky" />
            <StatCard label="Outstanding" :value="money(stats.outstanding_amount)" :icon="Wallet" tone="rose" />
        </section>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <SearchInput v-model="search" placeholder="Search invoice or customer…" />
            <FormSelect v-model="customerId" :options="customerOptions" placeholder="All customers" />
            <FormInput v-model="fromDate" type="date" label="From" />
            <FormInput v-model="toDate" type="date" label="To" />
            <button
                type="button"
                class="self-end rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                @click="resetFilters"
            >
                Reset filters
            </button>
        </div>

        <div class="mt-4">
            <DataTable
                :columns="columns"
                :rows="rows"
                empty-message="No sales found. Record a sale to get started."
                show-actions
            >
                <template #cell-invoice_number="{ value }">
                    <span class="font-medium text-gray-900">{{ value }}</span>
                </template>
                <template #cell-customer="{ row }">
                    {{ row.customer?.name ?? '—' }}
                </template>
                <template #cell-sale_date="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-discount_amount="{ value }">
                    <span class="text-amber-700">{{ money(value) }}</span>
                </template>
                <template #cell-total_amount="{ value }">
                    <span class="font-medium text-gray-900">{{ money(value) }}</span>
                </template>
                <template #cell-paid_amount="{ value }">
                    <span class="text-emerald-700">{{ money(value) }}</span>
                </template>
                <template #cell-due_amount="{ value }">
                    <span class="inline-flex items-center gap-1 font-medium" :class="Number(value) > 0 ? 'text-rose-700' : 'text-gray-400'">
                        <Clock3 v-if="Number(value) > 0" :size="13" />
                        {{ money(value) }}
                    </span>
                </template>
                <template #cell-payment_status="{ value }">
                    <StatusBadge :status="value" />
                </template>
                <template #cell-created_by="{ row }">
                    {{ row.created_by?.name ?? '—' }}
                </template>
                <template #cell-created_at="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #actions="{ row }">
                    <div class="flex justify-end">
                        <Link
                            :href="route('sales.show', row.id)"
                            class="rounded-md p-1.5 text-gray-500 hover:bg-indigo-50 hover:text-indigo-700"
                            :aria-label="`View ${row.invoice_number}`"
                        >
                            <Eye :size="16" />
                        </Link>
                    </div>
                </template>
            </DataTable>

            <Pagination :paginator="props.sales" />
        </div>
    </div>
</template>
