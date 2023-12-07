import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import Toast from "vue-toastification";
import {createVuetify} from 'vuetify';
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import '@mdi/font/css/materialdesignicons.min.css';
import 'font-awesome/css/font-awesome.css';
import '@fortawesome/fontawesome-free/css/all.css'; // Font Awesome Free (SVG with JS)
import VueMultiselect from "vue-multiselect";
// Import the CSS or use your own!
import "vue-toastification/dist/index.css";
import i18n from './i18n';
import VueSidebarMenu from 'vue-sidebar-menu'

import router from './router';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

const vuetify = createVuetify({
    components,
    directives
})

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(i18n)
            .use(Toast)
            .use(VueSidebarMenu)
            .use(VueMultiselect)
            .use(vuetify)
            .use(ZiggyVue, Ziggy)
            .use(router)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
