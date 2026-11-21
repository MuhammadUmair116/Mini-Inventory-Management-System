<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Pencil, Plus, Truck, Trash2, XCircle } from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import DataTable from '../../../Components/DataTable.vue';
import FormInput from '../../../Components/FormInput.vue';
import FormSelect from '../../../Components/FormSelect.vue';
import FormTextarea from '../../../Components/FormTextarea.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import SearchInput from '../../../Components/SearchInput.vue';
import StatCard from '../../../Components/StatCard.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useDebounce } from '../../../Composables/useDebounce';
import { formatDate } from '../../../Utils/formatDate';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    suppliers: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', is_active: '' }),
    },
    stats: {
        type: Object,
        required: true,
    },
});

const columns = [
    { key: 'name', label: 'Supplier' },
    { key: 'contact_person', label: 'Contact person' },
    { key: 'email', label: 'Email' },
    { key: 'phone', label: 'Phone' },
    { key: 'is_active', label: 'Status' },
    { key: 'created_at', label: 'Created' },
];

const activeOptions = [
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

const rows = computed(() => props.suppliers.data);
const search = ref(props.filters.search ?? '');
const isActive = ref(String(props.filters.is_active ?? ''));

const showForm = ref(false);
const editing = ref(null);
const deleting = ref(null);

const form = useForm({
    name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    is_active: true,
});

function fillForm(values) {
    form.name = values.name ?? '';
    form.contact_person = values.contact_person ?? '';
    form.email = values.email ?? '';
    form.phone = values.phone ?? '';
    form.address = values.address ?? '';
    form.is_active = values.is_active ?? true;
}

function openCreate() {
    editing.value = null;
    fillForm({});
    form.clearErrors();
    showForm.value = true;
}

function openEdit(supplier) {
    editing.value = supplier;
    fillForm(supplier);
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
        form.put(route('suppliers.update', editing.value.id), options);
    } else {
        form.post(route('suppliers.store'), options);
    }
}

function confirmDelete() {
    router.delete(route('suppliers.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
        },
    });
}

const applyFilters = useDebounce(() => {
    router.get(
        route('suppliers.index'),
        {
            search: search.value,
            is_active: isActive.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}, 300);

watch([search, isActive], () => applyFilters());

function resetFilters() {
    search.value = '';
    isActive.value = '';
}
</script>

<template>
    <div>
        <PageHeader
            title="Suppliers"
            description="Manage the vendors that supply your inventory."
        >
            <template #actions>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="openCreate"
                >
                    <Plus :size="16" />
                    New supplier
                </button>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total suppliers" :value="stats.total" :icon="Truck" tone="indigo" />
            <StatCard label="Active" :value="stats.active" :icon="CheckCircle2" tone="emerald" />
            <StatCard label="Inactive" :value="stats.inactive" :icon="XCircle" tone="sky" />
            <StatCard label="With purchases" :value="stats.with_purchases" :icon="Truck" tone="amber" />
        </section>

        <div class="mt-6 grid gap-3 sm:grid-cols-3">
            <SearchInput v-model="search" placeholder="Search name, contact, or email…" />
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
                empty-message="No suppliers found. Adjust the filters or create a supplier to get started."
                show-actions
            >
                <template #cell-name="{ row }">
                    <span class="font-medium text-gray-900">{{ row.name }}</span>
                </template>
                <template #cell-contact_person="{ value }">
                    {{ value || '—' }}
                </template>
                <template #cell-email="{ value }">
                    {{ value || '—' }}
                </template>
                <template #cell-phone="{ value }">
                    {{ value || '—' }}
                </template>
                <template #cell-is_active="{ row }">
                    <StatusBadge :status="row.is_active ? 'active' : 'inactive'" />
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

            <Pagination :paginator="props.suppliers" />
        </div>

        <Modal :show="showForm" max-width="2xl" @close="closeForm">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="text-base font-semibold text-gray-900">
                    {{ editing ? 'Edit supplier' : 'Create supplier' }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Supplier details are used on purchase documents.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <FormInput
                        v-model="form.name"
                        label="Name"
                        placeholder="e.g. Tech World Supplies"
                        :error="form.errors.name"
                        required
                    />
                    <FormInput
                        v-model="form.contact_person"
                        label="Contact person"
                        placeholder="Optional contact name"
                        :error="form.errors.contact_person"
                    />
                    <FormInput
                        v-model="form.email"
                        label="Email"
                        type="email"
                        placeholder="vendor@example.com"
                        :error="form.errors.email"
                    />
                    <FormInput
                        v-model="form.phone"
                        label="Phone"
                        placeholder="Optional phone number"
                        :error="form.errors.phone"
                    />
                    <div class="sm:col-span-2">
                        <FormTextarea
                            v-model="form.address"
                            label="Address"
                            placeholder="Optional address"
                            :error="form.errors.address"
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
                        {{ form.processing ? 'Saving…' : editing ? 'Update supplier' : 'Create supplier' }}
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            :show="deleting !== null"
            title="Delete supplier?"
            :message="`Are you sure you want to delete “${deleting?.name ?? ''}”? Suppliers with purchase history cannot be deleted.`"
            confirm-label="Delete"
            danger
            @close="deleting = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
