<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Boxes } from '@lucide/vue';

const page = usePage();
const appName = computed(() => page.props.settings?.app_name || 'Mini Inventory');

defineProps({
    compact: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <div class="flex items-center gap-3">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm">
            <Boxes :size="20" :stroke-width="2" />
        </span>
        <span v-if="!compact" class="text-base font-semibold tracking-tight text-gray-900">
            {{ appName }}
        </span>
    </div>
</template>
