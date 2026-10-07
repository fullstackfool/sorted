import type { Page, PageProps } from '@inertiajs/core';
import type { FlashMessages, Organisation } from '~/types';


interface SharedProps {
    app: {
        name: string;
        logo: string;
        icon: string;
    };
    auth: {
        user: {
            id: string;
            isWooshiiDev: boolean;
            isWooshiiAdmin: boolean;
            isWooshii: boolean;
            profile: {
                id: string;
                active_organisation_id: Organisation['id'] | null;
                given_name: string;
                family_name: string;
                name: string;
                email: string;
                is_wooshii: boolean;
                picture: string;
                organisations: Organisation[];
                active_organisation: Organisation;
            };
        } | null;
    };
    /**
     * Available on all pages that include the organisation slug.
     */
    contextOrganisation?: Organisation;
    flash: FlashMessages;
    quantumEnabled: boolean;
    referrer: {
        name: string | null;
        url: string | null;
    };
}

declare module '@inertiajs/vue3' {
    function usePage<SP extends PageProps>(): Page<SP & SharedProps>;
}
