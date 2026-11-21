<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Pencil, Plus, ShoppingBag, Trash2, Users, XCircle } from '@lucide/vue';
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
    customers: {
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
    can: {
        type: Object,
        default: () => ({ create: false, update: false, delete: false }),
    },
});

const columns = [
    { key: 'name', label: 'Customer' },
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

const rows = computed(() => props.customers.data);
const showActions = computed(() => props.can.update || props.can.delete);
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

function openEdit(customer) {
    editing.value = customer;
    fillForm(customer);
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
        form.put(route('customers.update', editing.value.id), options);
    } else {
        form.post(route('customers.store'), options);
    }
}

function confirmDelete() {
    router.delete(route('customers.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
        },
    });
}

const applyFilters = useDebounce(() => {
    router.get(
        route('customers.index'),
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
            title="Customers"
            description="Manage the customers who purchase from your inventory."
        >
            <template #actions>
                <button
                    v-if="can.create"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="openCreate"
                >
                    <Plus :size="16" />
                    New customer
                </button>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total customers" :value="stats.total" :icon="Users" tone="indigo" />
            <StatCard label="Active" :value="stats.active" :icon="CheckCircle2" tone="emerald" />
            <StatCard label="Inactive" :value="stats.inactive" :icon="XCircle" tone="sky" />
            <StatCard label="With sales" :value="stats.with_sales" :icon="ShoppingBag" tone="amber" />
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
                empty-message="No customers found. Adjust the filters or create a customer to get started."
                :show-actions="showActions"
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
                    <div v-if="showActions" class="flex justify-end gap-1">
                        <button
                            v-if="can.update"
                            type="button"
                            class="rounded-md p-1.5 text-gray-500 hover:bg-indigo-50 hover:text-indigo-700"
                            :aria-label="`Edit ${row.name}`"
                            @click="openEdit(row)"
                        >
                            <Pencil :size="16" />
                        </button>
                        <button
                            v-if="can.delete"
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

            <Pagination :paginator="props.customers" />
        </div>

        <Modal :show="showForm" max-width="2xl" @close="closeForm">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="text-base font-semibold text-gray-900">
                    {{ editing ? 'Edit customer' : 'Create customer' }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Customer details are shown on sales documents.
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <FormInput
                        v-model="form.name"
                        label="Name"
                        placeholder="e.g. Green Valley Retail"
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
                        placeholder="customer@example.com"
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
                        {{ form.processing ? 'Saving…' : editing ? 'Update customer' : 'Create customer' }}
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            :show="deleting !== null"
            title="Delete customer?"
            :message="`Are you sure you want to delete “${deleting?.name ?? ''}”? Customers with sales records cannot be deleted.`"
            confirm-label="Delete"
            danger
            @close="deleting = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
