import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Permission } from '@/types/auth';

export function usePermission() {
    const page = usePage();

    const permissions = computed<Permission[]>(
        () => page.props.auth.permissions ?? [],
    );

    const can = (permission: Permission): boolean =>
        permissions.value.includes(permission);

    const canAny = (...perms: Permission[]): boolean => perms.some((p) => can(p));

    const canAll = (...perms: Permission[]): boolean => perms.every((p) => can(p));

    const hasRole = (role: string): boolean =>
        page.props.auth.user.roles.some((r) => r === role);
    const isAdmin = computed(() => hasRole('admin') || hasRole('super_admin'));
    const isManager = computed(() => hasRole('manager'));
    const isTechnician = computed(() => hasRole('technician'));
    const isCashier = computed(() => hasRole('cashier'));

    return {
        can,
        canAny,
        canAll,
        hasRole,
        isAdmin,
        isManager,
        isTechnician,
        isCashier,
        permissions,
    };
}
