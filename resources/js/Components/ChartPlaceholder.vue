<script setup>
defineOptions({
    inheritAttrs: false,
});

defineProps({
    height: {
        type: [Number, String],
        default: 300,
    },
});
</script>

<template>
    <div
        class="flex items-center justify-center rounded-lg border border-dashed border-gray-200 bg-gray-50 text-xs text-gray-400"
        :style="{ height: `${height}px` }"
        role="status"
        aria-live="polite"
    >
        Loading chart…
    </div>
</template>
