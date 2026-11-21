<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CalendarDays, CreditCard, Plus, Wallet } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import FormInput from '../../Components/FormInput.vue';
import FormSelect from '../../Components/FormSelect.vue';
import FormTextarea from '../../Components/FormTextarea.vue';
import Modal from '../../Components/Modal.vue';
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
    payments: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', sale_id: '', method: '', from_date: '', to_date: '' }),
    },
    stats: {
        type: Object,
        required: true,
    },
    saleOptions: {
        type: Array,
        default: () => [],
    },
    methodOptions: {
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
    { key: 'receipt_number', label: 'Receipt' },
    { key: 'sale', label: 'Sale' },
    { key: 'payment_date', label: 'Payment date' },
    { key: 'method', label: 'Method' },
    { key: 'amount', label: 'Amount', align: 'right' },
    { key: 'reference', label: 'Reference' },
    { key: 'created_by', label: 'Recorded by' },
    { key: 'created_at', label: 'Created' },
];

const rows = computed(() => props.payments.data);
const search = ref(props.filters.search ?? '');
const saleId = ref(String(props.filters.sale_id ?? ''));
const method = ref(props.filters.method ?? '');
const fromDate = ref(props.filters.from_date ?? '');
const toDate = ref(props.filters.to_date ?? '');
const today = new Date().toISOString().slice(0, 10);

const showForm = ref(false);

const form = useForm({
    sale_id: '',
    receipt_number: '',
    payment_date: today,
    amount: '',
    method: 'cash',
    reference: '',
    notes: '',
});

const selectedSale = computed(() =>
    props.saleOptions.find((option) => String(option.value) === String(form.sale_id)),
);

function openCreate() {
    form.reset();
    form.payment_date = today;
    form.method = 'cash';
    form.clearErrors();
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    form.clearErrors();
}

function submit() {
    form.post(route('payments.store'), {
        preserveScroll: true,
        onSuccess: () => closeForm(),
    });
}

function methodLabel(value) {
    return props.methodOptions.find((option) => option.value === value)?.label
        ?? String(value ?? '').replaceAll('_', ' ');
}

const applyFilters = useDebounce(() => {
    router.get(
        route('payments.index'),
        {
            search: search.value,
            sale_id: saleId.value || undefined,
            method: method.value || undefined,
            from_date: fromDate.value || undefined,
            to_date: toDate.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}, 300);

watch([search, saleId, method, fromDate, toDate], () => applyFilters());

function resetFilters() {
    search.value = '';
    saleId.value = '';
    method.value = '';
    fromDate.value = '';
    toDate.value = '';
}
</script>

<template>
    <div>
        <PageHeader
            title="Payments"
            description="Record customer receipts and track outstanding sale balances."
        >
            <template #actions>
                <button
                    v-if="can.create"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="openCreate"
                >
                    <Plus :size="16" />
                    Record payment
                </button>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total collected" :value="money(stats.total_collected)" :icon="Wallet" tone="emerald" />
            <StatCard label="This month" :value="money(stats.this_month_collected)" :icon="CalendarDays" tone="sky" />
            <StatCard label="Outstanding" :value="money(stats.outstanding_total)" :icon="CreditCard" tone="rose" />
            <StatCard label="Receipts" :value="stats.payment_count" :icon="CreditCard" tone="indigo" />
        </section>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <SearchInput v-model="search" placeholder="Search receipt, sale, or customer…" />
            <FormSelect v-model="saleId" :options="saleOptions" placeholder="All sales" />
            <FormSelect v-model="method" :options="methodOptions" placeholder="All methods" />
            <FormInput v-model="fromDate" type="date" label="From" />
            <FormInput v-model="toDate" type="date" label="To" />
        </div>

        <div class="mt-3 flex justify-end">
            <button
                type="button"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50"
                @click="resetFilters"
            >
                Reset filters
            </button>
        </div>

        <div class="mt-4">
            <DataTable
                :columns="columns"
                :rows="rows"
                empty-message="No payments found. Adjust the filters or record a payment to get started."
            >
                <template #cell-receipt_number="{ value }">
                    <span class="font-medium text-gray-900">{{ value }}</span>
                </template>
                <template #cell-sale="{ row }">
                    <div class="flex flex-col">
                        <span class="font-medium text-gray-900">{{ row.sale?.invoice_number ?? '—' }}</span>
                        <span class="text-xs text-gray-400">{{ row.sale?.customer?.name ?? '' }}</span>
                    </div>
                </template>
                <template #cell-payment_date="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #cell-method="{ value }">
                    <span class="capitalize">{{ methodLabel(value) }}</span>
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
                <template #cell-created_at="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #footer>
                    <div class="flex justify-end gap-8">
                        <span class="text-sm text-gray-500">Displayed page total</span>
                        <span class="text-base font-semibold text-gray-900">
                            {{ money(rows.reduce((sum, row) => sum + Number(row.amount || 0), 0)) }}
                        </span>
                    </div>
                </template>
            </DataTable>

            <Pagination :paginator="props.payments" />
        </div>

        <Modal :show="showForm" max-width="2xl" @close="closeForm">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="text-base font-semibold text-gray-900">Record payment</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Payments are applied against the selected sale's outstanding balance.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <FormSelect
                            v-model="form.sale_id"
                            label="Sale"
                            :options="saleOptions"
                            placeholder="Select a sale with an outstanding balance"
                            :error="form.errors.sale_id"
                            required
                        />
                        <p v-if="selectedSale" class="mt-1 text-xs text-gray-400">
                            Outstanding: {{ money(selectedSale.due) }}
                        </p>
                    </div>
                    <FormInput
                        v-model="form.receipt_number"
                        label="Receipt number"
                        placeholder="REC-2026-0001"
                        :error="form.errors.receipt_number"
                        required
                    />
                    <FormInput
                        v-model="form.payment_date"
                        label="Payment date"
                        type="date"
                        :max="today"
                        :error="form.errors.payment_date"
                        required
                    />
                    <FormInput
                        v-model="form.amount"
                        label="Amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :error="form.errors.amount"
                        required
                    />
                    <FormSelect
                        v-model="form.method"
                        label="Method"
                        :options="methodOptions"
                        :error="form.errors.method"
                        required
                    />
                    <FormInput
                        v-model="form.reference"
                        label="Reference"
                        placeholder="Optional transaction reference"
                        :error="form.errors.reference"
                    />
                    <div class="sm:col-span-2">
                        <FormTextarea
                            v-model="form.notes"
                            label="Notes"
                            placeholder="Optional payment notes"
                            :error="form.errors.notes"
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        @click="closeForm"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        :disabled="form.processing || !saleOptions.length"
                    >
                        {{ form.processing ? 'Recording…' : 'Record payment' }}
                    </button>
                </div>
            </form>
        </Modal>
    </div>
</template>
