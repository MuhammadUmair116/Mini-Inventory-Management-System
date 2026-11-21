<script setup>
import { ref } from 'vue';
import { Bell, ChevronDown, LogOut, Menu, UserRound } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import { useAuthStore } from '../Stores/auth';

defineProps({
    title: {
        type: String,
        required: true,
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['toggle-sidebar']);

const auth = useAuthStore();
const profileOpen = ref(false);
</script>

<template>
    <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white/95 px-4 backdrop-blur sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="rounded-md p-2 text-gray-500 hover:bg-gray-100 lg:hidden"
                aria-label="Open navigation"
                @click="emit('toggle-sidebar')"
            >
                <Menu :size="20" />
            </button>

            <div class="min-w-0">
                <nav
                    v-if="breadcrumbs.length"
                    class="flex items-center gap-1 text-xs text-gray-400"
                    aria-label="Breadcrumb"
                >
                    <template v-for="(crumb, index) in breadcrumbs" :key="`${crumb.label}-${index}`">
                        <span v-if="index > 0">/</span>
                        <span
                            :class="index === breadcrumbs.length - 1 ? 'text-gray-600' : ''"
                            :aria-current="index === breadcrumbs.length - 1 ? 'page' : undefined"
                        >
                            {{ crumb.label }}
                        </span>
                    </template>
                </nav>
                <h1 class="truncate text-lg font-semibold text-gray-900">{{ title }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                class="relative rounded-lg p-2 text-gray-500 hover:bg-gray-100"
                aria-label="Notifications"
            >
                <Bell :size="19" />
                <span class="absolute right-1.5 top-1.5 size-2 rounded-full bg-rose-500" />
            </button>

            <div class="relative">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-gray-100"
                    aria-label="Open user menu"
                    aria-controls="profile-menu"
                    :aria-expanded="profileOpen"
                    @click="profileOpen = !profileOpen"
                >
                    <span class="flex size-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-700">
                        <UserRound :size="17" />
                    </span>
                    <span class="hidden text-left sm:block">
                        <span class="block font-medium text-gray-800">{{ auth.user?.name }}</span>
                        <span class="block text-xs capitalize text-gray-400">{{ auth.user?.role }}</span>
                    </span>
                    <ChevronDown :size="15" class="text-gray-400" />
                </button>

                <div
                    v-if="profileOpen"
                    id="profile-menu"
                    class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg"
                >
                    <Link
                        v-if="route().has('profile.edit')"
                        :href="route('profile.edit')"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                        @click="profileOpen = false"
                    >
                        <UserRound :size="16" />
                        Profile
                    </Link>
                    <button
                        type="button"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                        @click="auth.logout()"
                    >
                        <LogOut :size="16" />
                        Log out
                    </button>
                </div>
            </div>
        </div>
    </header>
</template>
