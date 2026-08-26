import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { createInvue } from 'invue/core';
import { Notifications } from 'invue/notifications';
import { Check, CircleCheck, CircleX, FileText, Info, MapPin, TriangleAlert, X } from '@lucide/vue';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const invue = createInvue();

        // Explicit, per-icon imports above — not a "resolve any Lucide name
        // by string" helper — so only the icons this app actually uses ship
        // in the bundle. See invue/core's Icon.vue for why.
        invue.registerIcons({
            check: Check,
            'circle-check': CircleCheck,
            'circle-x': CircleX,
            'file-text': FileText,
            info: Info,
            'map-pin': MapPin,
            'triangle-alert': TriangleAlert,
            x: X,
        });

        // Mounted once, app-wide (not just inside invue/panels pages) —
        // Notifications is deliberately decoupled from Panels, same as
        // Filament's own notifications render regardless of which page
        // triggered them.
        return createApp({ render: () => [h(App, props), h(Notifications)] })
            .use(plugin)
            .use(ZiggyVue)
            .use(invue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
