import { router } from '@inertiajs/vue3';
import type { Directive } from 'vue';
import type { Permission } from '@/types/auth';

function readPermissionsFromDom(): Permission[] {
    try {
        const data = document.getElementById('app')?.dataset?.page;

        if (!data) {
            return [];
        }

        const page = JSON.parse(data) as {
            props?: { auth?: { permissions?: Permission[] } };
        };

        return page?.props?.auth?.permissions ?? [];
    } catch {
        return [];
    }
}

let currentPermissions: Permission[] = readPermissionsFromDom();

router.on('navigate', (event) => {
    currentPermissions =
        (event.detail.page.props?.auth as { permissions?: Permission[] })
            ?.permissions ?? [];
});

export const vPermission: Directive<HTMLElement, Permission | Permission[]> = {
    beforeMount(el, binding) {
        applyPermission(el, binding.value);
    },
    updated(el, binding) {
        applyPermission(el, binding.value);
    },
};

function applyPermission(
    el: HTMLElement,
    value: Permission | Permission[],
): void {
    const required = Array.isArray(value) ? value : [value];
    const allowed = required.some((p) => currentPermissions.includes(p));
    el.style.display = allowed ? '' : 'none';
}
