<script setup>
import { ArrowDownRight, ArrowUpRight } from '@lucide/vue';

defineProps({
    label: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    icon: {
        type: Object,
        required: true,
    },
    hint: {
        type: String,
        default: '',
    },
    trend: {
        type: Number,
        default: null,
    },
    tone: {
        type: String,
        default: 'indigo',
    },
});

const tones = {
    indigo: 'bg-indigo-50 text-indigo-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    amber: 'bg-amber-50 text-amber-600',
    rose: 'bg-rose-50 text-rose-600',
    sky: 'bg-sky-50 text-sky-600',
};
</script>

<template>
    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-sm font-medium text-gray-500">{{ label }}</p>
                <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">{{ value }}</p>
            </div>
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                :class="tones[tone] ?? tones.indigo"
            >
                <component :is="icon" :size="19" :stroke-width="1.9" />
            </span>
        </div>
        <div v-if="hint || trend !== null" class="mt-3 flex items-center gap-1.5 text-xs">
            <template v-if="trend !== null">
                <component :is="trend >= 0 ? ArrowUpRight : ArrowDownRight" :size="14" />
                <span :class="trend >= 0 ? 'text-emerald-600' : 'text-rose-600'">
                    {{ Math.abs(trend) }}%
                </span>
            </template>
            <span v-if="hint" class="text-gray-400">{{ hint }}</span>
        </div>
    </div>
</template>
