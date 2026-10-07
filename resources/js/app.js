import './bootstrap';
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import { Ziggy } from './ziggy.js';
import Toast, { TYPE } from 'vue-toastification';
import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faShare } from '@fortawesome/free-solid-svg-icons';

import 'vue-toastification/dist/index.css';

/* add icons to the library */
library.add(faShare);

// Configure Inertia router to use the current page's origin (respects HTTPS/HTTP)
// This ensures that when the page is loaded over HTTPS, all Inertia requests also use HTTPS
if (typeof window !== 'undefined' && window.location) {
    // Override the visit method to ensure all URLs use the current page's protocol
    const originalVisit = router.visit;
    router.visit        = function (url, options = {}) {
        // Convert HTTP URLs to use the current page's protocol (HTTPS/HTTP)
        if (url && typeof url === 'string' && url.startsWith('http://')) {
            url = url.replace(/^http:\/\//, window.location.protocol + '//');
        }
        return originalVisit.call(this, url, options);
    };
}

const toastOptions = {
    pauseOnFocusLoss: false,
    pauseOnHover: false,
    toastDefaults: {
        [TYPE.ERROR]: {
            timeout: 5000,
        },
        [TYPE.SUCCESS]: {
            timeout: 2000,
            hideProgressBar: true,
        },
    },
};

createInertiaApp({
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${ name }.vue`];
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(Toast, toastOptions)
            .component('font-awesome-icon', FontAwesomeIcon)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
