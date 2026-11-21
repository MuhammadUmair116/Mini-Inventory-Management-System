<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, CreditCard, Warehouse } from '@lucide/vue';
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
    sale: {
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
    { key: 'discount_percentage', label: 'Discount', align: 'right' },
    { key: 'line_total', label: 'Line total', align: 'right' },
];

const paymentColumns = [
    { key: 'receipt_number', label: 'Receipt' },
    { key: 'payment_date', label: 'Date' },
    { key: 'method', label: 'Method' },
    { key: 'amount', label: 'Amount', align: 'right' },
    { key: 'reference', label: 'Reference' },
    { key: 'created_by', label: 'Recorded by' },
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

function formatPercentage(value) {
    return `${Number(value ?? 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    })}%`;
}
</script>

<template>
    <div>
        <PageHeader
            :title="`Sale ${props.sale.invoice_number}`"
            description="Sale document, line items, and inventory impact."
        >
            <template #actions>
                <Link
                    :href="route('sales.index')"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                >
                    <ArrowLeft :size="16" />
                    Back to sales
                </Link>
            </template>
        </PageHeader>

        <section class="mt-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-6">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Customer</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">{{ props.sale.customer?.name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Sale date</p>
                    <p class="mt-1 text-sm text-gray-700">{{ formatDate(props.sale.sale_date) }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Status</p>
                    <p class="mt-1"><StatusBadge :status="props.sale.status" /></p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Payment</p>
                    <p class="mt-1"><StatusBadge :status="props.sale.payment_status" /></p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Recorded by</p>
                    <p class="mt-1 text-sm text-gray-700">{{ props.sale.created_by?.name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-gray-400">Total amount</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900">{{ money(props.sale.total_amount) }}</p>
                </div>
            </div>

            <div class="mt-5 grid gap-4 border-t border-gray-100 pt-4 sm:grid-cols-5">
                <div>
                    <p class="text-xs text-gray-400">Subtotal</p>
                    <p class="text-sm font-medium text-gray-800">{{ money(props.sale.subtotal_amount) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Discount</p>
                    <p class="text-sm font-medium text-amber-700">{{ money(props.sale.discount_amount) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Net total</p>
                    <p class="text-sm font-semibold text-gray-900">{{ money(props.sale.total_amount) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Paid</p>
                    <p class="text-sm font-medium text-emerald-700">{{ money(props.sale.paid_amount) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400">Due</p>
                    <p class="text-sm font-semibold text-rose-700">{{ money(props.sale.due_amount) }}</p>
                </div>
            </div>

            <p v-if="props.sale.notes" class="mt-5 border-t border-gray-100 pt-4 text-sm text-gray-600">
                {{ props.sale.notes }}
            </p>
        </section>

        <section class="mt-6">
            <div class="mb-3 flex items-center gap-2">
                <ClipboardList :size="18" class="text-gray-400" />
                <h2 class="text-sm font-semibold text-gray-700">Line items</h2>
            </div>
            <DataTable :columns="itemColumns" :rows="props.sale.items ?? []">
                <template #cell-product_name="{ row }">
                    <span class="font-medium text-gray-900">{{ row.product_name }}</span>
                </template>
                <template #cell-quantity="{ value }">
                    {{ formatQuantity(value) }}
                </template>
                <template #cell-unit_price="{ value }">
                    {{ money(value) }}
                </template>
                <template #cell-discount_percentage="{ value }">
                    <span class="text-amber-700">{{ formatPercentage(value) }}</span>
                </template>
                <template #cell-line_total="{ value }">
                    <span class="font-medium text-gray-900">{{ money(value) }}</span>
                </template>
                <template #footer>
                    <div class="flex justify-end gap-8">
                        <span class="text-sm text-gray-500">Total</span>
                        <span class="text-base font-semibold text-gray-900">{{ money(props.sale.total_amount) }}</span>
                    </div>
                </template>
            </DataTable>
        </section>

        <section class="mt-8">
            <div class="mb-3 flex items-center gap-2">
                <CreditCard :size="18" class="text-gray-400" />
                <h2 class="text-sm font-semibold text-gray-700">Payments</h2>
            </div>
            <DataTable
                :columns="paymentColumns"
                :rows="props.sale.payments ?? []"
                empty-message="No payments have been recorded for this sale."
            >
                <template #cell-receipt_number="{ value }">
                    <span class="font-medium text-gray-900">{{ value }}</span>
                </template>
                <template #cell-payment_date="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-method="{ value }">
                    <span class="capitalize">{{ String(value ?? '').replaceAll('_', ' ') }}</span>
                </template>
                <template #cell-amount="{ value }">
                    <span class="font-medium text-emerald-700">{{ money(value) }}</span>
                </template>
                <template #cell-reference="{ value }">
                    {{ value || '—' }}
                </template>
                <template #cell-created_by="{ row }">
                    {{ row.created_by?.name ?? '—' }}
                </template>
                <template #footer>
                    <div class="flex justify-end gap-8">
                        <span class="text-sm text-gray-500">Paid to date</span>
                        <span class="text-base font-semibold text-emerald-700">{{ money(props.sale.paid_amount) }}</span>
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
                :rows="props.sale.stock_movements ?? []"
                empty-message="No stock movements were recorded for this sale."
            >
                <template #cell-product="{ row }">
                    {{ row.product?.name ?? '—' }}
                </template>
                <template #cell-type="{ value }">
                    <StatusBadge :status="value === 'sale' ? 'completed' : value" />
                </template>
                <template #cell-quantity="{ value }">
                    <span class="font-medium text-rose-700">-{{ formatQuantity(value) }}</span>
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
