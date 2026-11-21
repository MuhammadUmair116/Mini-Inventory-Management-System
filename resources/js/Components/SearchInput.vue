<script setup>
import { Search, X } from '@lucide/vue';

defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Search…',
    },
});

const emit = defineEmits(['update:modelValue']);
</script>

<template>
    <div class="relative w-full sm:max-w-xs">
        <Search
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"
            :size="17"
        />
        <input
            type="search"
            :value="modelValue"
            :placeholder="placeholder"
            :aria-label="placeholder"
            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-9 text-sm text-gray-800 placeholder:text-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20"
            @input="emit('update:modelValue', $event.target.value)"
        />
        <button
            v-if="modelValue"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
            aria-label="Clear search"
            @click="emit('update:modelValue', '')"
        >
            <X :size="15" />
        </button>
    </div>
</template>
