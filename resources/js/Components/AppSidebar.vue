<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    ClipboardList,
    CreditCard,
    LayoutDashboard,
    Package,
    Ruler,
    Settings,
    ShieldCheck,
    ShoppingCart,
    Tags,
    Truck,
    Users,
    Warehouse,
    X,
} from '@lucide/vue';
import AppLogo from './AppLogo.vue';
import { usePermissions } from '../Composables/usePermissions';

defineProps({
    open: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const page = usePage();
const { can } = usePermissions();
const companyName = computed(() => page.props.settings?.company_name || 'Inventory Manager v1.0');

const navigation = [
    { label: 'Dashboard', routeName: 'dashboard', icon: LayoutDashboard, permission: 'dashboard.view' },
    { label: 'Products', routeName: 'products.index', icon: Package, permission: 'products.view' },
    { label: 'Categories', routeName: 'categories.index', icon: Tags, permission: 'categories.view' },
    { label: 'Units', routeName: 'units.index', icon: Ruler, permission: 'units.view' },
    { label: 'Purchases', routeName: 'purchases.index', icon: ClipboardList, permission: 'purchases.view' },
    { label: 'Sales', routeName: 'sales.index', icon: ShoppingCart, permission: 'sales.view' },
    { label: 'Inventory', routeName: 'inventory.index', icon: Warehouse, permission: 'inventory.view' },
    { label: 'Suppliers', routeName: 'suppliers.index', icon: Truck, permission: 'suppliers.view' },
    { label: 'Customers', routeName: 'customers.index', icon: Users, permission: 'customers.view' },
    { label: 'Payments', routeName: 'payments.index', icon: CreditCard, permission: 'payments.view' },
    { label: 'Reports', routeName: 'reports.sales', icon: BarChart3, permission: 'reports.view' },
    { label: 'Users', routeName: 'users.index', icon: ShieldCheck, permission: 'users.view' },
    { label: 'Settings', routeName: 'settings.index', icon: Settings, permission: 'settings.view' },
];

const availableNavigation = computed(() =>
    navigation.filter(
        (item) => route().has(item.routeName) && can(item.permission),
    ),
);

const isActive = (routeName) => route().current(routeName);
</script>

<template>
    <div>
        <div
            v-if="open"
            class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden"
            @click="emit('close')"
        />

        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-gray-200 bg-white transition-transform duration-200 lg:translate-x-0"
            :class="open ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-gray-200 px-5">
                <AppLogo />
                <button
                    type="button"
                    class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 lg:hidden"
                    aria-label="Close navigation"
                    @click="emit('close')"
                >
                    <X :size="20" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4" aria-label="Main navigation">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                    Workspace
                </p>
                <ul class="space-y-1">
                    <li v-for="item in availableNavigation" :key="item.routeName">
                        <Link
                            :href="route(item.routeName)"
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                            :class="
                                isActive(item.routeName)
                                    ? 'bg-indigo-50 text-indigo-700'
                                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                            "
                            :aria-current="isActive(item.routeName) ? 'page' : undefined"
                            @click="emit('close')"
                        >
                            <component :is="item.icon" :size="18" :stroke-width="1.9" />
                            {{ item.label }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <div class="border-t border-gray-200 px-5 py-4 text-xs text-gray-400">
                {{ companyName }}
            </div>
        </aside>
    </div>
</template>
