<script setup>
defineProps({
    label: {
        type: String,
        default: 'Loading…',
    },
    small: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="flex items-center justify-center gap-2 text-gray-500" role="status">
        <span
            class="animate-spin rounded-full border-2 border-gray-300 border-t-indigo-600"
            :class="small ? 'size-4' : 'size-6'"
        />
        <span v-if="label" :class="small ? 'text-xs' : 'text-sm'">{{ label }}</span>
    </div>
</template>
