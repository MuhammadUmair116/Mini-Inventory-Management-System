<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    paginator: {
        type: Object,
        required: true,
    },
});

const links = computed(() => props.paginator.links ?? []);

const meta = computed(() => ({
    from: props.paginator.from ?? null,
    to: props.paginator.to ?? null,
    total: props.paginator.total ?? 0,
}));
</script>

<template>
    <div
        v-if="links.length > 3"
        class="flex flex-col gap-3 border-t border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-xs text-gray-500">
            <template v-if="meta.from && meta.to">
                Showing {{ meta.from }}–{{ meta.to }} of {{ meta.total }}
            </template>
            <template v-else>
                {{ meta.total }} total records
            </template>
        </p>

        <nav class="flex flex-wrap items-center gap-1" aria-label="Pagination">
            <template v-for="(link, index) in links" :key="`${link.label}-${index}`">
                <span
                    v-if="!link.url"
                    class="inline-flex size-8 items-center justify-center rounded-md text-xs font-medium text-gray-300"
                    aria-disabled="true"
                    v-html="link.label"
                />
                <Link
                    v-else
                    :href="link.url"
                    :aria-current="link.active ? 'page' : undefined"
                    class="inline-flex min-w-8 items-center justify-center rounded-md px-2 py-1.5 text-xs font-medium transition-colors"
                    :class="link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
