<template>
    <MainLayout>
        <div class="min-h-screen bg-gray-900 text-gray-100">
            <div class="max-w-7xl mx-auto p-6">
                <!-- Back Button -->
                <Link :href="route('users.index')" class="inline-flex items-center gap-2 text-gray-400 hover:text-gray-200 mb-6 transition">
                    <svg class="w-5 h-5"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Users
                </Link>

                <div class="bg-gray-800 rounded-lg p-8 border border-gray-700 mb-6">
                    <h1 class="text-3xl font-bold mb-6">New User</h1>

                    <div class="space-y-6">
                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Name *</label>
                            <input v-model="form.name"
                                   type="text"
                                   required
                                   placeholder="e.g. Karl"
                                   class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded text-white text-lg focus:outline-none focus:border-blue-500" />
                        </div>

                        <!-- Avatar Preview -->
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-3">Choose an Avatar</label>
                            <div class="flex items-center gap-6 mb-4">
                                <div class="w-28 h-28 rounded-full overflow-hidden bg-gray-700 flex items-center justify-center border-4 border-gray-600">
                                    <img v-if="form.avatar_style"
                                         :src="previewUrl"
                                         alt="Avatar preview"
                                         class="w-full h-full" />
                                    <span v-else class="text-4xl font-bold text-gray-500">?</span>
                                </div>
                                <div v-if="form.avatar_style" class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2">
                                        <IconButton variant="gray" size="sm" @click="prevSeed" :title="'Previous'" class="!px-3" :class="{ 'opacity-30 pointer-events-none': !canGoBack }">
                                            ◀
                                        </IconButton>
                                        <IconButton variant="blue" size="sm" @click="randomizeSeed">
                                            🎲 Randomize
                                        </IconButton>
                                        <IconButton variant="gray" size="sm" @click="nextSeed" :title="'Next'" class="!px-3" :class="{ 'opacity-30 pointer-events-none': !canGoForward }">
                                            ▶
                                        </IconButton>
                                    </div>
                                    <p class="text-xs text-gray-500">{{ historyIndex + 1 }} of {{ history.length }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Avatar Style Grid -->
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-3">Pick a Style</label>
                            <div class="grid grid-cols-4 gap-4">
                                <button v-for="style in avatarStyles"
                                        :key="style.id"
                                        @click="selectStyle(style.id)"
                                        class="p-4 rounded-lg border-2 transition flex flex-col items-center gap-2"
                                        :class="form.avatar_style === style.id
                                            ? 'border-blue-500 bg-blue-900/30'
                                            : 'border-gray-700 bg-gray-750 hover:border-gray-500'">
                                    <div class="w-16 h-16 rounded-full overflow-hidden bg-gray-700">
                                        <img :src="getStylePreview(style.id)"
                                             :alt="style.label"
                                             class="w-full h-full" />
                                    </div>
                                    <span class="text-sm">{{ style.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-4">
                            <IconButton variant="blue" size="md" @click="saveUser">
                                Create User
                            </IconButton>
                            <IconButton variant="gray" size="md" :href="route('users.index')">
                                Cancel
                            </IconButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import IconButton from '@/Components/IconButton.vue';

const avatarStyles = [
    { id: 'bottts', label: 'Robots' },
    { id: 'fun-emoji', label: 'Emoji' },
    { id: 'adventurer-neutral', label: 'Adv Neutral' },
    { id: 'adventurer', label: 'Adventurer' },
    { id: 'big-smile', label: 'Big Smile' },
    { id: 'croodles', label: 'Doodles' },
    { id: 'thumbs', label: 'Thumbs' },
    { id: 'lorelei', label: 'Lorelei' },
];

const generateSeed = () => Math.random().toString(36).substring(2, 10);

// History stores full { style, seed } entries
const history = ref([]);
const historyIndex = ref(-1);

const form = ref({
    name: '',
    avatar_style: null,
    avatar_seed: generateSeed(),
});

const canGoBack = computed(() => historyIndex.value > 0);
const canGoForward = computed(() => historyIndex.value < history.value.length - 1);

const previewUrl = computed(() => {
    if (!form.value.avatar_style) return null;
    return `https://api.dicebear.com/9.x/${form.value.avatar_style}/svg?seed=${form.value.avatar_seed}`;
});

const getStylePreview = (styleId) => {
    return `https://api.dicebear.com/9.x/${styleId}/svg?seed=preview`;
};

const applyHistoryEntry = (entry) => {
    form.value.avatar_style = entry.style;
    form.value.avatar_seed = entry.seed;
};

const isDuplicate = (entry) => {
    return history.value.some(h => h.style === entry.style && h.seed === entry.seed);
};

const pushEntry = (entry) => {
    if (isDuplicate(entry)) {
        // Jump to the existing entry instead
        const idx = history.value.findIndex(h => h.style === entry.style && h.seed === entry.seed);
        historyIndex.value = idx;
    } else {
        history.value.push(entry);
        historyIndex.value = history.value.length - 1;
    }
    applyHistoryEntry(entry);
};

const selectStyle = (styleId) => {
    pushEntry({ style: styleId, seed: form.value.avatar_seed });
};

const randomizeSeed = () => {
    if (!form.value.avatar_style) return;
    // generateSeed is random so duplicates are near-impossible, but check anyway
    let entry;
    do {
        entry = { style: form.value.avatar_style, seed: generateSeed() };
    } while (isDuplicate(entry));
    pushEntry(entry);
};

const prevSeed = () => {
    if (canGoBack.value) {
        historyIndex.value--;
        applyHistoryEntry(history.value[historyIndex.value]);
    }
};

const nextSeed = () => {
    if (canGoForward.value) {
        historyIndex.value++;
        applyHistoryEntry(history.value[historyIndex.value]);
    }
};

const saveUser = () => {
    if (!form.value.name.trim()) {
        alert('Please enter a name.');
        return;
    }

    router.post(route('users.store'), {
        name: form.value.name,
        avatar_style: form.value.avatar_style,
        avatar_seed: form.value.avatar_seed,
    });
};
</script>
