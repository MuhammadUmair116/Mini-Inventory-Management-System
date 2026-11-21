<script setup>
defineProps({
    columns: {
        type: Array,
        required: true,
    },
    rows: {
        type: Array,
        default: () => [],
    },
    rowKey: {
        type: String,
        default: 'id',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    emptyMessage: {
        type: String,
        default: 'No records found.',
    },
    caption: {
        type: String,
        default: '',
    },
    showActions: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <caption v-if="caption" class="sr-only">{{ caption }}</caption>
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500"
                            :class="column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : 'text-left'"
                        >
                            {{ column.label }}
                        </th>
                        <th
                            v-if="showActions"
                            scope="col"
                            class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500"
                        >
                            Actions
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-100 bg-white">
                    <tr v-if="loading">
                        <td
                            :colspan="columns.length + (showActions ? 1 : 0)"
                            class="px-4 py-10 text-center text-sm text-gray-500"
                            role="status"
                            aria-live="polite"
                        >
                            Loading…
                        </td>
                    </tr>
                    <tr v-else-if="rows.length === 0">
                        <td
                            :colspan="columns.length + (showActions ? 1 : 0)"
                            class="px-4 py-10 text-center text-sm text-gray-500"
                        >
                            <slot name="empty">
                                {{ emptyMessage }}
                            </slot>
                        </td>
                    </tr>
                    <tr
                        v-for="row in rows"
                        v-else
                        :key="row[rowKey]"
                        class="transition-colors hover:bg-gray-50/70"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-3 align-middle text-gray-700"
                            :class="column.align === 'right' ? 'text-right' : column.align === 'center' ? 'text-center' : 'text-left'"
                        >
                            <slot
                                :name="`cell-${column.key}`"
                                :row="row"
                                :value="row[column.key]"
                            >
                                {{ row[column.key] ?? '—' }}
                            </slot>
                        </td>
                        <td v-if="showActions" class="px-4 py-3 text-right align-middle">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="$slots.footer" class="border-t border-gray-200 bg-gray-50/60 px-4 py-3">
            <slot name="footer" />
        </div>
    </div>
</template>
