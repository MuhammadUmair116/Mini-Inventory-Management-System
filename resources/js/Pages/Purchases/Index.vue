<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { CalendarDays, ClipboardList, Eye, Package, Plus, Wallet } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import FormInput from '../../Components/FormInput.vue';
import FormSelect from '../../Components/FormSelect.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import SearchInput from '../../Components/SearchInput.vue';
import StatCard from '../../Components/StatCard.vue';
import { useCurrency } from '../../Composables/useCurrency';
import { useDebounce } from '../../Composables/useDebounce';
import { formatDate } from '../../Utils/formatDate';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    purchases: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', supplier_id: '', from_date: '', to_date: '' }),
    },
    stats: {
        type: Object,
        required: true,
    },
    supplierOptions: {
        type: Array,
        default: () => [],
    },
});

const { money } = useCurrency();

const columns = [
    { key: 'invoice_number', label: 'Invoice' },
    { key: 'supplier', label: 'Supplier' },
    { key: 'purchase_date', label: 'Purchase date' },
    { key: 'items_count', label: 'Items', align: 'right' },
    { key: 'total_amount', label: 'Total', align: 'right' },
    { key: 'created_by', label: 'Recorded by' },
    { key: 'created_at', label: 'Created' },
];

const rows = computed(() => props.purchases.data);
const search = ref(props.filters.search ?? '');
const supplierId = ref(String(props.filters.supplier_id ?? ''));
const fromDate = ref(props.filters.from_date ?? '');
const toDate = ref(props.filters.to_date ?? '');

const applyFilters = useDebounce(() => {
    router.get(
        route('purchases.index'),
        {
            search: search.value,
            supplier_id: supplierId.value || undefined,
            from_date: fromDate.value || undefined,
            to_date: toDate.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}, 300);

watch([search, supplierId, fromDate, toDate], () => applyFilters());

function resetFilters() {
    search.value = '';
    supplierId.value = '';
    fromDate.value = '';
    toDate.value = '';
}
</script>

<template>
    <div>
        <PageHeader
            title="Purchases"
            description="Record supplier deliveries and track the stock movements they create."
        >
            <template #actions>
                <Link
                    :href="route('purchases.create')"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    <Plus :size="16" />
                    New purchase
                </Link>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total purchases" :value="stats.total" :icon="ClipboardList" tone="indigo" />
            <StatCard label="Purchase value" :value="money(stats.total_amount)" :icon="Wallet" tone="emerald" />
            <StatCard label="This month" :value="stats.this_month" :icon="CalendarDays" tone="amber" />
            <StatCard label="Line items" :value="stats.items" :icon="Package" tone="sky" />
        </section>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <SearchInput v-model="search" placeholder="Search invoice or supplier…" />
            <FormSelect v-model="supplierId" :options="supplierOptions" placeholder="All suppliers" />
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
                empty-message="No purchases found. Record a purchase to get started."
                show-actions
            >
                <template #cell-invoice_number="{ value }">
                    <span class="font-medium text-gray-900">{{ value }}</span>
                </template>
                <template #cell-supplier="{ row }">
                    {{ row.supplier?.name ?? '—' }}
                </template>
                <template #cell-purchase_date="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-total_amount="{ value }">
                    <span class="font-medium text-gray-900">{{ money(value) }}</span>
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
                            :href="route('purchases.show', row.id)"
                            class="rounded-md p-1.5 text-gray-500 hover:bg-indigo-50 hover:text-indigo-700"
                            :aria-label="`View ${row.invoice_number}`"
                        >
                            <Eye :size="16" />
                        </Link>
                    </div>
                </template>
            </DataTable>

            <Pagination :paginator="props.purchases" />
        </div>
    </div>
</template>
