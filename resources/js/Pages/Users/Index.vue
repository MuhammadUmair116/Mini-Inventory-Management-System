<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import {
    Pencil,
    Plus,
    ShieldCheck,
    UserCheck,
    UserRound,
    Users,
} from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import DataTable from '../../Components/DataTable.vue';
import FormInput from '../../Components/FormInput.vue';
import FormSelect from '../../Components/FormSelect.vue';
import Modal from '../../Components/Modal.vue';
import PageHeader from '../../Components/PageHeader.vue';
import Pagination from '../../Components/Pagination.vue';
import SearchInput from '../../Components/SearchInput.vue';
import StatCard from '../../Components/StatCard.vue';
import StatusBadge from '../../Components/StatusBadge.vue';
import { useDebounce } from '../../Composables/useDebounce';
import { formatDate } from '../../Utils/formatDate';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '', role: '', status: '' }),
    },
    stats: {
        type: Object,
        required: true,
    },
    roleOptions: {
        type: Array,
        default: () => [],
    },
    statusOptions: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const currentUserId = computed(() => page.props.auth?.user?.id);
const rows = computed(() => props.users.data);

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'email', label: 'Email' },
    { key: 'role', label: 'Role' },
    { key: 'status', label: 'Status' },
    { key: 'created_at', label: 'Created' },
];

const search = ref(props.filters.search ?? '');
const role = ref(String(props.filters.role ?? ''));
const status = ref(String(props.filters.status ?? ''));

const showForm = ref(false);
const editing = ref(null);

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'staff',
    status: 'active',
});

const isEditingSelf = computed(() => editing.value?.id === currentUserId.value);

function fillForm(user) {
    form.name = user.name ?? '';
    form.email = user.email ?? '';
    form.password = '';
    form.password_confirmation = '';
    form.role = user.role ?? 'staff';
    form.status = user.status ?? 'active';
}

function openCreate() {
    editing.value = null;
    fillForm({ role: 'staff', status: 'active' });
    form.clearErrors();
    showForm.value = true;
}

function openEdit(user) {
    editing.value = user;
    fillForm(user);
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
        form.put(route('users.update', editing.value.id), options);
    } else {
        form.post(route('users.store'), options);
    }
}

function resetFilters() {
    search.value = '';
    role.value = '';
    status.value = '';
}

const applyFilters = useDebounce(() => {
    router.get(
        route('users.index'),
        {
            search: search.value,
            role: role.value || undefined,
            status: status.value || undefined,
        },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}, 300);

watch([search, role, status], () => applyFilters());
</script>

<template>
    <div>
        <PageHeader
            title="Users"
            description="Manage who can access the workspace and what each account can do."
        >
            <template #actions>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="openCreate"
                >
                    <Plus :size="16" />
                    New user
                </button>
            </template>
        </PageHeader>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total accounts" :value="stats.total" :icon="Users" tone="indigo" />
            <StatCard label="Administrators" :value="stats.admins" :icon="ShieldCheck" tone="sky" />
            <StatCard label="Staff members" :value="stats.staff" :icon="UserRound" tone="emerald" />
            <StatCard label="Inactive" :value="stats.inactive" :icon="UserCheck" tone="amber" />
        </section>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <SearchInput v-model="search" placeholder="Search name or email…" />
            <FormSelect v-model="role" :options="props.roleOptions" placeholder="All roles" />
            <FormSelect v-model="status" :options="props.statusOptions" placeholder="All statuses" />
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
                empty-message="No accounts match the selected filters."
                show-actions
            >
                <template #cell-name="{ row }">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-900">{{ row.name }}</span>
                            <span
                                v-if="row.id === currentUserId"
                                class="rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"
                            >
                                You
                            </span>
                        </div>
                        <span v-if="row.role === 'admin'" class="mt-0.5 block text-xs text-indigo-500">
                            Full access
                        </span>
                    </div>
                </template>
                <template #cell-email="{ value }">
                    <span class="text-gray-600">{{ value }}</span>
                </template>
                <template #cell-role="{ value }">
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ring-1 ring-inset"
                        :class="value === 'admin'
                            ? 'bg-indigo-50 text-indigo-700 ring-indigo-600/20'
                            : 'bg-gray-100 text-gray-600 ring-gray-500/20'"
                    >
                        {{ value }}
                    </span>
                </template>
                <template #cell-status="{ value }">
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
                    </div>
                </template>
            </DataTable>

            <Pagination :paginator="props.users" />
        </div>

        <Modal :show="showForm" max-width="lg" @close="closeForm">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="text-base font-semibold text-gray-900">
                    {{ editing ? 'Edit user' : 'Create user' }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    {{ editing ? 'Update account details and access level.' : 'Add a new account to the workspace.' }}
                </p>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <FormInput
                        v-model="form.name"
                        label="Name"
                        placeholder="Full name"
                        :error="form.errors.name"
                        required
                    />
                    <FormInput
                        v-model="form.email"
                        label="Email"
                        type="email"
                        placeholder="name@example.com"
                        :error="form.errors.email"
                        required
                    />
                    <FormInput
                        v-model="form.password"
                        label="Password"
                        type="password"
                        autocomplete="new-password"
                        :hint="editing ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.'"
                        :error="form.errors.password"
                        :required="!editing"
                    />
                    <FormInput
                        v-model="form.password_confirmation"
                        label="Confirm password"
                        type="password"
                        autocomplete="new-password"
                        :error="form.errors.password_confirmation"
                        :required="!editing"
                    />
                    <FormSelect
                        v-model="form.role"
                        label="Role"
                        :options="props.roleOptions"
                        :error="form.errors.role"
                        required
                    />
                    <FormSelect
                        v-model="form.status"
                        label="Status"
                        :options="props.statusOptions"
                        :error="form.errors.status"
                        required
                    />
                </div>

                <p class="mt-4 text-xs text-gray-400">
                    At least one active administrator must remain. Inactive accounts cannot sign in.
                </p>

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
                        {{ form.processing ? 'Saving…' : editing ? 'Update user' : 'Create user' }}
                    </button>
                </div>
            </form>
        </Modal>
    </div>
</template>
