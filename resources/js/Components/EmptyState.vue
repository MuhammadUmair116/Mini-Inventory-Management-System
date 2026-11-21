<script setup>
import { Inbox } from '@lucide/vue';

defineProps({
    title: {
        type: String,
        default: 'Nothing here yet',
    },
    description: {
        type: String,
        default: '',
    },
    icon: {
        type: Object,
        default: Inbox,
    },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
        <span class="flex size-12 items-center justify-center rounded-full bg-gray-100 text-gray-400">
            <component :is="icon" :size="22" :stroke-width="1.8" />
        </span>
        <h3 class="mt-4 text-sm font-semibold text-gray-900">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-gray-500">{{ description }}</p>
        <div v-if="$slots.action" class="mt-5">
            <slot name="action" />
        </div>
    </div>
</template>
