
interface ImportMetaEnv {
    VITE_INSIGHT_MAIN_WEB_URL: string;
    VITE_APP_NAME: string;
    VITE_FACEBOOK_CLIENT_ID: string;
    VITE_PUSHER_APP_KEY: string;
    VITE_PUSHER_APP_CLUSTER: string;
    VITE_SEGMENT_KEY: string;
    VITE_VAPOR_ASSET_URL: string;
    VITE_ATLAS_CHARTS_BASE_URL: string;
    VITE_IDENTITY_APP_URL: string;
    VITE_QUANTUM_CHAT_SUGGESTIONS_ENABLED: string | undefined;
    VITE_AI_PROXY_URL: string;
}

declare const __SENTRY_RELEASE__: string;
