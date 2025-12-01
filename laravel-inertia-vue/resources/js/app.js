import './bootstrap';
import '../css/app.css';
import axios from 'axios';

// Ensure axios has CSRF header and sends cookies. bootstrap.js sets these, but make sure
// they're present for modules that import axios directly.
const tokenMeta = document.querySelector('meta[name="csrf-token"]')
if (tokenMeta && !axios.defaults.headers.common['X-CSRF-TOKEN']) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.getAttribute('content')
}
axios.defaults.withCredentials = true;

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
