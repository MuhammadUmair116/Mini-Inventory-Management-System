<script setup>
import { computed, ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import AppLayout from '../../../Layouts/AppLayout.vue';
import ConfirmDialog from '../../../Components/ConfirmDialog.vue';
import DataTable from '../../../Components/DataTable.vue';
import FormInput from '../../../Components/FormInput.vue';
import FormTextarea from '../../../Components/FormTextarea.vue';
import Modal from '../../../Components/Modal.vue';
import PageHeader from '../../../Components/PageHeader.vue';
import Pagination from '../../../Components/Pagination.vue';
import SearchInput from '../../../Components/SearchInput.vue';
import StatusBadge from '../../../Components/StatusBadge.vue';
import { useDebounce } from '../../../Composables/useDebounce';
import { formatDate } from '../../../Utils/formatDate';

defineOptions({
    layout: AppLayout,
});

const props = defineProps({
    categories: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const columns = [
    { key: 'name', label: 'Name' },
    { key: 'slug', label: 'Slug' },
    { key: 'description', label: 'Description' },
    { key: 'is_active', label: 'Status' },
    { key: 'created_at', label: 'Created' },
];

const rows = computed(() => props.categories.data);
const search = ref(props.filters.search ?? '');
const showForm = ref(false);
const editing = ref(null);
const deleting = ref(null);

const form = useForm({
    name: '',
    slug: '',
    description: '',
    is_active: true,
});

function fillForm(values) {
    form.name = values.name ?? '';
    form.slug = values.slug ?? '';
    form.description = values.description ?? '';
    form.is_active = values.is_active ?? true;
}

function openCreate() {
    editing.value = null;
    fillForm({ name: '', slug: '', description: '', is_active: true });
    form.clearErrors();
    showForm.value = true;
}

function openEdit(category) {
    editing.value = category;
    fillForm(category);
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
        form.put(route('categories.update', editing.value.id), options);
    } else {
        form.post(route('categories.store'), options);
    }
}

function confirmDelete() {
    router.delete(route('categories.destroy', deleting.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
        },
    });
}

const applySearch = useDebounce((value) => {
    router.get(
        route('categories.index'),
        { search: value },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}, 300);

watch(search, (value) => applySearch(value));
</script>

<template>
    <div>
        <PageHeader
            title="Categories"
            description="Organize products into meaningful groups."
        >
            <template #actions>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="openCreate"
                >
                    <Plus :size="16" />
                    New category
                </button>
            </template>
        </PageHeader>

        <div class="mb-4">
            <SearchInput v-model="search" placeholder="Search categories…" />
        </div>

        <DataTable
            :columns="columns"
            :rows="rows"
            empty-message="No categories found. Create your first category to get started."
            show-actions
        >
            <template #cell-name="{ row }">
                <span class="font-medium text-gray-900">{{ row.name }}</span>
            </template>
            <template #cell-slug="{ value }">
                <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600">{{ value }}</code>
            </template>
            <template #cell-description="{ value }">
                <span class="text-gray-500">{{ value || '—' }}</span>
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

        <Pagination :paginator="props.categories" />

        <Modal :show="showForm" max-width="lg" @close="closeForm">
            <form class="p-6" @submit.prevent="submit">
                <h3 class="text-base font-semibold text-gray-900">
                    {{ editing ? 'Edit category' : 'Create category' }}
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Categories help keep the product catalogue organized.
                </p>

                <div class="mt-5 space-y-4">
                    <FormInput
                        v-model="form.name"
                        label="Name"
                        placeholder="e.g. Electronics"
                        :error="form.errors.name"
                        required
                    />
                    <FormInput
                        v-model="form.slug"
                        label="Slug"
                        placeholder="electronics"
                        hint="Leave blank to generate automatically from the name."
                        :error="form.errors.slug"
                    />
                    <FormTextarea
                        v-model="form.description"
                        label="Description"
                        placeholder="Optional description"
                        :error="form.errors.description"
                    />
                    <label class="flex items-center gap-2 text-sm text-gray-700">
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
                        {{ form.processing ? 'Saving…' : editing ? 'Update category' : 'Create category' }}
                    </button>
                </div>
            </form>
        </Modal>

        <ConfirmDialog
            :show="deleting !== null"
            title="Delete category?"
            :message="`Are you sure you want to delete “${deleting?.name ?? ''}”? This cannot be undone.`"
            confirm-label="Delete"
            danger
            @close="deleting = null"
            @confirm="confirmDelete"
        />
    </div>
</template>
