import { createInertiaApp, router } from '@inertiajs/vue3';
import 'vue-sonner/style.css';
import { createPinia } from 'pinia';
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import { vPermission } from '@/directives/permission';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

if (import.meta.env.DEV) {
    router.on('httpException', (event) => {
        const response = (event as CustomEvent).detail?.response;

        if (response?.data && typeof response.data === 'string') {
            document.open();
            document.write(response.data);
            document.close();
        }
    });
}

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    progress: {
        color: '#4B5563',
    },
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true }) as Record<string, DefineComponent>;

        return pages[`./pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(createPinia())
            .directive('permission', vPermission)
            .mount(el);
    },
});
