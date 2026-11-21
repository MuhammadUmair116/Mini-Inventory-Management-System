<script setup>
import { computed, ref, watch } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';
import AppHeader from '../Components/AppHeader.vue';
import AppSidebar from '../Components/AppSidebar.vue';
import NotificationToast from '../Components/NotificationToast.vue';
import { useAuthStore } from '../Stores/auth';
import { useNotifications } from '../Composables/useNotifications';

const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const auth = useAuthStore();
const notifications = useNotifications();
const sidebarOpen = ref(false);

const routeLabels = {
    dashboard: 'Dashboard',
    products: 'Products',
    purchases: 'Purchases',
    sales: 'Sales',
    inventory: 'Inventory',
    payments: 'Payments',
    reports: 'Reports',
    customers: 'Customers',
    suppliers: 'Suppliers',
    categories: 'Categories',
    units: 'Units',
    users: 'Users',
    settings: 'Settings',
};

const headerTitle = computed(() => {
    if (props.title || page.props.title) {
        return props.title || page.props.title;
    }

    const currentRoute = route().current() ?? 'dashboard';
    const section = currentRoute.split('.')[0].replaceAll('_', ' ');

    return routeLabels[section]
        ?? section.replace(/\b\w/g, (letter) => letter.toUpperCase());
});

watch(
    () => page.props.auth?.user,
    (user) => auth.setUser(user),
    { immediate: true },
);

watch(
    () => page.props.flash?.success,
    (message) => {
        if (message) {
            notifications.success(message);
        }
    },
    { immediate: true },
);

watch(
    () => page.props.flash?.error,
    (message) => {
        if (message) {
            notifications.error(message);
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <Head :title="headerTitle" />

        <AppSidebar :open="sidebarOpen" @close="sidebarOpen = false" />

        <div class="lg:pl-64">
            <AppHeader
                :title="headerTitle"
                :breadcrumbs="breadcrumbs"
                @toggle-sidebar="sidebarOpen = true"
            />

            <main class="mx-auto w-full max-w-[1500px] px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>

        <NotificationToast />
    </div>
</template>
