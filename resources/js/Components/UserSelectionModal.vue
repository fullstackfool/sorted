<template>
    <!-- Modal Overlay -->
    <div v-if="show"
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
         @click="closeModal">
        <!-- Modal Content -->
        <div class="bg-gray-800 rounded-lg p-8 max-w-md w-full mx-4 border border-gray-700"
             @click.stop>
            <h2 class="text-2xl font-bold mb-6 text-center">
                Who are you?
            </h2>

            <!-- User Grid -->
            <div class="grid grid-cols-2 gap-4 mb-6">
                <button v-for="user in users"
                        :key="user.id"
                        class="p-6 bg-gray-700 hover:bg-gray-600 rounded-lg transition group"
                        @click="selectUser(user)">
                    <!-- Avatar -->
                    <div class="w-16 h-16 mx-auto mb-3 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-2xl font-bold group-hover:scale-110 transition">
                        <img v-if="user.avatar_url"
                             :src="user.avatar_url"
                             :alt="user.name"
                             class="w-full h-full" />
                        <span v-else>{{ getInitials(user.name) }}</span>
                    </div>
                    <!-- Name -->
                    <div class="text-center font-medium">
                        {{ user.name }}
                    </div>
                </button>
            </div>

            <!-- Cancel Button -->
            <IconButton variant="gray"
                        size="md"
                        class="w-full"
                        @click="closeModal">
                Cancel
            </IconButton>
        </div>
    </div>
</template>

<script setup>
import IconButton from '@/Components/IconButton.vue';

defineProps({
    show: Boolean,
    users: Array,
});

const emit = defineEmits(['close', 'select']);

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const selectUser = (user) => {
    emit('select', user);
};

const closeModal = () => {
    emit('close');
};
</script>

