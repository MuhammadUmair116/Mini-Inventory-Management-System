import { useAuthStore } from '../Stores/auth';

export function usePermissions() {
    const auth = useAuthStore();

    const can = (permission) => {
        if (!auth.user) {
            return false;
        }

        if (auth.isAdmin) {
            return true;
        }

        const staffPermissions = [
            'dashboard.view',
            'products.view',
            'customers.view',
            'sales.view',
            'sales.create',
            'inventory.view',
            'payments.view',
            'reports.view',
        ];

        return staffPermissions.includes(permission);
    };

    return { can, isAdmin: () => auth.isAdmin };
}
