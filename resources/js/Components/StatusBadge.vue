<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
});

const classes = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    inactive: 'bg-gray-100 text-gray-600 ring-gray-500/20',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    draft: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    cancelled: 'bg-rose-50 text-rose-700 ring-rose-600/20',
    in_stock: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    low_stock: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    out_of_stock: 'bg-rose-50 text-rose-700 ring-rose-600/20',
    paid: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    partial: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    unpaid: 'bg-rose-50 text-rose-700 ring-rose-600/20',
};

const label = computed(() =>
    props.status.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()),
);
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ring-1 ring-inset"
        :class="classes[status] ?? classes.inactive"
    >
        {{ label }}
    </span>
</template>
