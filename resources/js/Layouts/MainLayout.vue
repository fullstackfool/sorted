<template>
    <div class="flex flex-col h-screen bg-gray-900 text-gray-100">
        <!-- Header -->
        <header class="bg-gray-800 border-b border-gray-700 px-6 py-3 flex items-center gap-6">
            <!-- App Name -->
            <Link :href="route('home')" class="text-xl font-bold text-white shrink-0 hover:text-blue-400 transition">
                Sorted
            </Link>

            <!-- Navigation -->
            <nav class="flex items-center gap-1">
                <Link :href="route('home')"
                      :class="[
                          'flex items-center gap-2 px-3 py-2 text-sm transition border-b-2',
                          isActive('/')
                              ? 'text-white font-bold border-white'
                              : 'text-gray-400 font-medium border-transparent hover:text-white'
                      ]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Today
                </Link>
                <Link :href="route('templates.index')"
                      :class="[
                          'flex items-center gap-2 px-3 py-2 text-sm transition border-b-2',
                          isActive('/templates')
                              ? 'text-white font-bold border-white'
                              : 'text-gray-400 font-medium border-transparent hover:text-white'
                      ]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Templates
                </Link>
                <Link :href="route('users.index')"
                      :class="[
                          'flex items-center gap-2 px-3 py-2 text-sm transition border-b-2',
                          isActive('/users')
                              ? 'text-white font-bold border-white'
                              : 'text-gray-400 font-medium border-transparent hover:text-white'
                      ]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Users
                </Link>
            </nav>

            <!-- Spacer + Header Actions -->
            <div class="ml-auto flex items-center gap-2">
                <slot name="header-actions" />
            </div>
        </header>

        <!-- Page Content -->
        <div class="flex-1 overflow-y-auto">
            <slot />
        </div>
    </div>
</template>

<script lang="ts">
type FlashMessages = {
    error: string | null;
    success: string | null;
    info: string | null;
    warning: string | null;
};
</script>

<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import route from 'ziggy';
import { useToast } from 'vue-toastification';

const toast = useToast();

const page = usePage();

const isActive = (path) => {
    const currentPath = page.url;
    if (path === '/') {
        return currentPath === '/';
    }
    return currentPath.startsWith(path);
};


const parseFlash = (value: FlashMessages) => {
    if (value?.error) {
        toast.error(value.error);
    }
    if (value?.success) {
        toast.success(value.success);
    }
    if (value?.info) {
        toast.info(value.info);
    }
    if (value?.warning) {
        toast.warning(value.warning);
    }
};

onMounted(() => {
    setTimeout(() => {
        parseFlash(usePage().props.flash);
    }, 500);
});

watch(() => usePage().props.flash, parseFlash);

// Live(ish) updates between devices. The server rewrites /sync.txt whenever data changes
// (see App\Support\SyncStamp). The web server hands it out directly, so checking never starts PHP.
const SYNC_INTERVAL_MS = 5000;

let syncStamp = page.props.syncStamp as string;
let visiting = false;

watch(() => page.props.syncStamp, (stamp) => {
    syncStamp = stamp as string;
});

const checkForChanges = async () => {
    if (document.hidden || visiting) {
        return;
    }

    try {
        const response = await fetch('/sync.txt', { cache: 'no-store' });
        const stamp = response.ok ? (await response.text()).trim() : '';

        // Don't interrupt a tap that's still on its way to the server.
        if (stamp && stamp !== syncStamp && !visiting) {
            syncStamp = stamp;
            router.reload();
        }
    } catch {
        // Offline or the server is restarting: try again next time.
    }
};

const removeStartListener = router.on('start', () => {
    visiting = true;
});
const removeFinishListener = router.on('finish', () => {
    visiting = false;
});
let syncTimer: number | undefined;

onMounted(() => {
    syncTimer = window.setInterval(checkForChanges, SYNC_INTERVAL_MS);
    // Phones skip checks while hidden, so check as soon as they're back.
    document.addEventListener('visibilitychange', checkForChanges);
});

onUnmounted(() => {
    window.clearInterval(syncTimer);
    document.removeEventListener('visibilitychange', checkForChanges);
    removeStartListener();
    removeFinishListener();
});
</script>

