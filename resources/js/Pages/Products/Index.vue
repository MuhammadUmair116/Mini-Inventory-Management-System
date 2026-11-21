<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import {
    Boxes,
    CheckCircle2,
    CircleAlert,
    Pencil,
    Plus,
    Trash2,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import DataTable from '../../Components/DataTable.vue';
import FormInput from '../../Components/FormInput.vue';
import FormSelect from '../../Components/FormSelect.vue';
import FormTextarea from '../../Components/FormTextarea.vue';
import Modal from '../../Components/Modal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import SearchInput from '../../Components/SearchInput.vue';
import StatCard from '../../Components/StatCard.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useCurrency } from '../../Composables/useCurrency';
import { useDebounce } from '../../Composables/useDebounce';
import { usePermissions } from '../../Composables/usePermissions';
import { formatDate } from '../../Utils/formatDate';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            category_id: '',
            stock_status: '',
            is_active: '',
        }),
    },
    stats: {
        type: Object,
        required: true,
    },
    categoryOptions: {
        type: Array,
        default: () => [],
    },
    unitOptions: {
        type: Array,
        default: () => [],
    },
});

const { money } = useCurrency();
const { can } = usePermissions();
const canManage = computed(() => can('products.update'));

const columns = [
    { key: 'name', label: 'Product' },
    { key: 'category', label: 'Category' },
    { key: 'unit', label: 'Unit' },
    { key: 'purchase_price', label: 'Purchase price', align: 'right' },
    { key: 'sale_price', label: 'Sale price', align: 'right' },
    { key: 'current_stock', label: 'Stock', align: 'right' },
    { key: 'stock_status', label: 'Stock status' },
    { key: 'created_at', label: 'Created' },
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

const rows = computed(() => props.products.data);
const search = ref(props.filters.search ?? '');
const categoryId = ref(String(props.filters.category_id ?? ''));
const stockStatus = ref(props.filters.stock_status ?? '');
const isActive = ref(String(props.filters.is_active ?? ''));

const showForm = ref(false);
const editing = ref(null);
const deleting = ref(null);

const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    category_id: '',
    unit_id: '',
    description: '',
    purchase_price: '',
    sale_price: '',
    reorder_level: '',
    is_active: true,
});

function fillForm(values) {
    form.name = values.name ?? '';
    form.sku = values.sku ?? '';
    form.barcode = values.barcode ?? '';
    form.category_id = values.category_id ? String(values.category_id) : '';
    form.unit_id = values.unit_id ? String(values.unit_id) : '';
    form.description = values.description ?? '';
    form.purchase_price = values.purchase_price ?? '';
    form.sale_price = values.sale_price ?? '';
    form.reorder_level = values.reorder_level ?? '';
    form.is_active = values.is_active ?? true;
}

function openCreate() {
    editing.value = null;
    fillForm({});
    form.clearErrors();
    showForm.value = true;
}

function openEdit(product) {
    editing.value = product;
    fillForm(product);
    form.clearErrors();
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    form.clearErrors();
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => closeForm(),
    };

    if (editing.value) {
        form.put(route('products.update', editing.value.id), options);
    } else {
        form.post(route('products.store'), options);
    }
}

function confirmDelete() {
    router.delete(route('products.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
        },
    });
}

function formatQuantity(value) {
    return Number(value ?? 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
    });
}

const applyFilters = useDebounce(() => {
    router.get(
        route('products.index'),
        {
            search: search.value,
            category_id: categoryId.value || undefined,
            stock_status: stockStatus.value || undefined,
            is_active: isActive.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}, 300);

watch([search, categoryId, stockStatus, isActive], () => applyFilters());

function resetFilters() {
    search.value = '';
    categoryId.value = '';
    stockStatus.value = '';
    isActive.value = '';
}
</script>

<template>
    <div>
        <PageHeader
            title="Products"
            description="Manage your product catalogue, pricing, and stock thresholds."
        >
            <template #actions>
                <button
                    v-if="canManage"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="openCreate"
                >
                    <Plus :size="16" />
                    New product
                </button>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total products" :value="stats.total" :icon="Boxes" tone="indigo" />
            <StatCard label="Active products" :value="stats.active" :icon="CheckCircle2" tone="emerald" />
            <StatCard label="Low stock" :value="stats.low_stock" :icon="CircleAlert" tone="amber" />
            <StatCard label="Out of stock" :value="stats.out_of_stock" :icon="CircleAlert" tone="rose" />
        </section>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <SearchInput v-model="search" placeholder="Search name, SKU, barcode…" />
            <FormSelect v-model="categoryId" :options="categoryOptions" placeholder="All categories" />
            <FormSelect v-model="stockStatus" :options="stockStatusOptions" placeholder="All stock levels" />
            <FormSelect v-model="isActive" :options="activeOptions" placeholder="All statuses" />
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
                :show-actions="canManage"
                empty-message="No products found. Adjust the filters or create a product to get started."
            >
                <template #cell-name="{ row }">
                    <div>
                        <span class="font-medium text-gray-900">{{ row.name }}</span>
                        <span class="mt-0.5 block text-xs text-gray-400">{{ row.sku }}</span>
                    </div>
                </template>
                <template #cell-category="{ row }">
                    {{ row.category?.name ?? '—' }}
                </template>
                <template #cell-unit="{ row }">
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-600">
                        {{ row.unit?.short_name ?? '—' }}
                    </span>
                </template>
                <template #cell-purchase_price="{ value }">
                    {{ money(value) }}
                </template>
                <template #cell-sale_price="{ value }">
                    <span class="font-medium text-gray-900">{{ money(value) }}</span>
                </template>
                <template #cell-current_stock="{ row }">
                    {{ formatQuantity(row.current_stock) }} {{ row.unit?.short_name ?? '' }}
                </template>
                <template #cell-stock_status="{ value }">
                    <StatusBadge :status="value" />
                </template>
                <template #cell-created_at="{ value }">
                    {{ formatDate(value) }}
                </template>
                <template #actions="{ row }">
                    <div class="flex justify-end gap-1">
                        <button
                            type="button"
                            class="rounded-md p-1.5 text-gray-500 hover:bg-indigo-50 hover:text-indigo-700"
                            :aria-label="`Edit ${row.name}`"
                            @click="openEdit(row)"
                        >
                            <Pencil :size="16" />
                        </button>
                        <button
                            type="button"
                            class="rounded-md p-1.5 text-gray-500 hover:bg-rose-50 hover:text-rose-700"
                            :aria-label="`Delete ${row.name}`"
                            @click="deleting = row"
                        >
                            <Trash2 :size="16" />
                        </button>
                    </div>
                </template>
            </DataTable>

            <Pagination :paginator="props.products" />
        </div>

        <Modal v-if="canManage" :show="showForm" max-width="2xl" @close="closeForm">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="text-base font-semibold text-gray-900">
                    {{ editing ? 'Edit product' : 'Create product' }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Stock quantities are managed through inventory movements, not this form.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <FormInput
                        v-model="form.name"
                        label="Name"
                        placeholder="e.g. Business Laptop"
                        :error="form.errors.name"
                        required
                    />
                    <FormInput
                        v-model="form.sku"
                        label="SKU"
                        placeholder="ELEC-LAP-001"
                        :error="form.errors.sku"
                        required
                    />
                    <FormInput
                        v-model="form.barcode"
                        label="Barcode"
                        placeholder="Optional barcode"
                        :error="form.errors.barcode"
                    />
                    <FormSelect
                        v-model="form.category_id"
                        label="Category"
                        :options="categoryOptions"
                        placeholder="Select a category"
                        :error="form.errors.category_id"
                        required
                    />
                    <FormSelect
                        v-model="form.unit_id"
                        label="Unit"
                        :options="unitOptions"
                        placeholder="Select a unit"
                        :error="form.errors.unit_id"
                        required
                    />
                    <FormInput
                        v-model="form.purchase_price"
                        label="Purchase price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        :error="form.errors.purchase_price"
                        required
                    />
                    <FormInput
                        v-model="form.sale_price"
                        label="Sale price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        :error="form.errors.sale_price"
                        required
                    />
                    <FormInput
                        v-model="form.reorder_level"
                        label="Reorder level"
                        type="number"
                        step="0.001"
                        min="0"
                        placeholder="0.000"
                        hint="Low-stock alert threshold."
                        :error="form.errors.reorder_level"
                        required
                    />
                    <div class="sm:col-span-2">
                        <FormTextarea
                            v-model="form.description"
                            label="Description"
                            placeholder="Optional description"
                            :error="form.errors.description"
                        />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 sm:col-span-2">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        Active
                    </label>
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
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving…' : editing ? 'Update product' : 'Create product' }}
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            v-if="canManage"
            :show="deleting !== null"
            title="Delete product?"
            :message="`Are you sure you want to delete “${deleting?.name ?? ''}”? This cannot be undone.`"
            confirm-label="Delete"
            danger
            @close="deleting = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
