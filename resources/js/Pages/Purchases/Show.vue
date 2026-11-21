<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, Warehouse } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import PageHeader from '../../Components/PageHeader.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useCurrency } from '../../Composables/useCurrency';
import { formatDate } from '../../Utils/formatDate';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    purchase: {
        type: Object,
        required: true,
    },
});

const { money } = useCurrency();

const itemColumns = [
    { key: 'product_name', label: 'Product' },
    { key: 'sku', label: 'SKU' },
    { key: 'quantity', label: 'Quantity', align: 'right' },
    { key: 'unit_price', label: 'Unit price', align: 'right' },
    { key: 'line_total', label: 'Line total', align: 'right' },
];

const movementColumns = [
    { key: 'product', label: 'Product' },
    { key: 'type', label: 'Type' },
    { key: 'quantity', label: 'Quantity', align: 'right' },
    { key: 'balance_after', label: 'Balance after', align: 'right' },
    { key: 'created_at', label: 'Recorded at' },
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
            :title="`Purchase ${props.purchase.invoice_number}`"
            description="Purchase document, line items, and inventory impact."
        >
            <template #actions>
                <Link
                    :href="route('purchases.index')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    <ArrowLeft :size="16" />
                    Back to purchases
                </Link>
            </template>
        </PageHeader>

        <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Supplier</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ props.purchase.supplier?.name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Purchase date</p>
                    <p class="mt-1 text-sm text-gray-700">{{ formatDate(props.purchase.purchase_date) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Recorded by</p>
                    <p class="mt-1 text-sm text-gray-700">{{ props.purchase.created_by?.name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Total amount</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ money(props.purchase.total_amount) }}</p>
                </div>
            </div>
            <p v-if="props.purchase.notes" class="mt-5 border-t border-gray-100 pt-4 text-sm text-gray-600">
                {{ props.purchase.notes }}
            </p>
        </section>

        <section class="mt-6">
            <div class="mb-3 flex items-center gap-2">
                <ClipboardList :size="18" class="text-gray-400" />
                <h2 class="text-sm font-semibold text-gray-700">Line items</h2>
            </div>
            <DataTable :columns="itemColumns" :rows="props.purchase.items ?? []">
                <template #cell-product_name="{ row }">
                    <span class="font-medium text-gray-900">{{ row.product_name }}</span>
                </template>
                <template #cell-quantity="{ value }">
                    {{ formatQuantity(value) }}
                </template>
                <template #cell-unit_price="{ value }">
                    {{ money(value) }}
                </template>
                <template #cell-line_total="{ value }">
                    <span class="font-medium text-gray-900">{{ money(value) }}</span>
                </template>
                <template #footer>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Total</span>
                        <span class="text-base font-semibold text-gray-900">
                            {{ money(props.purchase.total_amount) }}
                        </span>
                    </div>
                </template>
            </DataTable>
        </section>

        <section class="mt-8">
            <div class="mb-3 flex items-center gap-2">
                <Warehouse :size="18" class="text-gray-400" />
                <h2 class="text-sm font-semibold text-gray-700">Stock movements</h2>
            </div>
            <DataTable
                :columns="movementColumns"
                :rows="props.purchase.stock_movements ?? []"
                empty-message="No stock movements were recorded for this purchase."
            >
                <template #cell-product="{ row }">
                    {{ row.product?.name ?? '—' }}
                </template>
                <template #cell-type="{ value }">
                    <StatusBadge :status="value === 'purchase' ? 'completed' : value" />
                </template>
                <template #cell-quantity="{ value }">
                    <span class="font-medium text-emerald-700">+{{ formatQuantity(value) }}</span>
                </template>
                <template #cell-balance_after="{ value }">
                    {{ formatQuantity(value) }}
                </template>
                <template #cell-created_at="{ value }">
                    {{ formatDate(value) }}
                </template>
            </DataTable>
        </section>
    </div>
</template>
