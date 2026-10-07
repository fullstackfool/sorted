import type Echo from 'laravel-echo';

declare module '*.vue' {
    import type { DefineComponent } from 'vue';
    const component: DefineComponent;
    export default component;
}

declare global {
    interface Window {

        Echo: Echo;
    }
}

import type { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

declare module 'vue' {
    export interface GlobalComponents {

        'Icon': typeof FontAwesomeIcon;
    }
}
