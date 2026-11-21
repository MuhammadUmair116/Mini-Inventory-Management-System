<script setup>
import AppLogo from '../Components/AppLogo.vue';

defineProps({
    title: {
        type: String,
        default: '',
    },
    description: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <main class="flex min-h-screen items-center justify-center bg-gray-50 px-4 py-12">
        <div class="w-full max-w-md">
            <div class="mb-6 flex justify-center">
                <AppLogo />
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:p-8">
                <div v-if="title || description" class="mb-6 text-center">
                    <h1 v-if="title" class="text-xl font-semibold text-gray-900">{{ title }}</h1>
                    <p v-if="description" class="mt-1 text-sm text-gray-500">{{ description }}</p>
                </div>

                <slot />
            </div>

            <p class="mt-6 text-center text-xs text-gray-400">
                {{ new Date().getFullYear() }} Mini Inventory. All rights reserved.
            </p>
        </div>
    </main>
</template>
