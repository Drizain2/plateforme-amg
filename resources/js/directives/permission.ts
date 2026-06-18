import type { Directive, VNode } from 'vue';
import type { Permission } from '@/types/auth';

export const vPermission: Directive<HTMLElement, Permission | Permission[]> = {
    beforeMount(el, binding, vnode) {
        applyPermission(el, binding.value, vnode);
    },
    updated(el, binding, vnode) {
        applyPermission(el, binding.value, vnode);
    },
};

function applyPermission(el: HTMLElement, value: Permission | Permission[], vnode: VNode): void {
    const page = vnode.appContext?.app.config.globalProperties.$page as
        | { props?: { auth?: { permissions?: Permission[] } } }
        | undefined;

    const permissions: Permission[] = page?.props?.auth?.permissions ?? [];
    const required = Array.isArray(value) ? value : [value];
    const allowed = required.some((p) => permissions.includes(p));

    el.style.display = allowed ? '' : 'none';
}
