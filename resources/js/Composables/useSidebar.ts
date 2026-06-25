import { ref, watch } from 'vue';

const STORAGE_KEY = 'sidebar:collapsed';

const collapsed = ref(localStorage.getItem(STORAGE_KEY) === '1');
const mobileOpen = ref(false);

watch(collapsed, (value) => {
    localStorage.setItem(STORAGE_KEY, value ? '1' : '0');
});

export function useSidebar() {
    function toggleCollapsed() {
        collapsed.value = !collapsed.value;
    }

    function openMobile() {
        mobileOpen.value = true;
    }

    function closeMobile() {
        mobileOpen.value = false;
    }

    function toggleMobile() {
        mobileOpen.value = !mobileOpen.value;
    }

    return {
        collapsed,
        mobileOpen,
        toggleCollapsed,
        openMobile,
        closeMobile,
        toggleMobile,
    };
}
