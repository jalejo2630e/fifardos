import '../css/app.css';
import './bootstrap';

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js');
}

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import Toaster from '@/Components/Toast/Toaster.vue';

const appName = import.meta.env.VITE_APP_NAME || 'FIFARDOS';

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        // Renderiza la página de Inertia + el Toaster global (persistente entre navegaciones)
        return createApp({ render: () => [h(App, props), h(Toaster)] })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#ff5f00',
    },
});
