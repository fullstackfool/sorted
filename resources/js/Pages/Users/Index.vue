<template>
    <MainLayout>
        <template #header-actions>
            <IconButton variant="blue" size="sm" :href="route('users.create')">
                + New User
            </IconButton>
        </template>

        <div class="min-h-screen bg-gray-900 text-gray-100">
            <div class="max-w-7xl mx-auto p-6">
                <!-- User Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Link v-for="user in userDetails"
                          :key="user.id"
                          :href="route('users.show', user.id)"
                          class="bg-gray-800 rounded-lg p-8 border-2 border-gray-700 hover:border-blue-500 transition-all cursor-pointer group">
                        <!-- User Header -->
                        <div class="flex items-center gap-4 mb-6">
                            <!-- Avatar Circle -->
                            <div class="w-20 h-20 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-3xl font-bold">
                                <img v-if="user.avatar_url"
                                     :src="user.avatar_url"
                                     :alt="user.name"
                                     class="w-full h-full" />
                                <span v-else>{{ getInitials(user.name) }}</span>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold group-hover:text-blue-400 transition">
                                    {{ user.name }}
                                </h2>
                                <p class="text-gray-400 text-sm">
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-3 gap-4">
                            <!-- Tasks Assigned -->
                            <div class="bg-gray-750 rounded-lg p-4 text-center">
                                <div class="text-3xl font-bold text-blue-400 mb-1">
                                    {{ user.tasks_assigned }}
                                </div>
                                <div class="text-xs text-gray-400 uppercase">
                                    Tasks
                                </div>
                            </div>

                            <!-- Tasks Completed -->
                            <div class="bg-gray-750 rounded-lg p-4 text-center">
                                <div class="text-3xl font-bold text-green-400 mb-1">
                                    {{ user.tasks_completed }}
                                </div>
                                <div class="text-xs text-gray-400 uppercase">
                                    Completed
                                </div>
                            </div>

                            <!-- Total Points -->
                            <div class="bg-gray-750 rounded-lg p-4 text-center">
                                <div class="text-3xl font-bold text-yellow-400 mb-1">
                                    {{ user.total_points }}
                                </div>
                                <div class="text-xs text-gray-400 uppercase">
                                    Points
                                </div>
                            </div>
                        </div>

                        <!-- Completion Rate -->
                        <div class="mt-4 pt-4 border-t border-gray-700">
                            <div class="flex items-center justify-between text-sm mb-2">
                                <span class="text-gray-400">Completion Rate</span>
                                <span class="font-semibold">{{ getCompletionRate(user) }}%</span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-2">
                                <div class="bg-gradient-to-r from-green-500 to-blue-500 h-2 rounded-full transition-all" :style="{ width: getCompletionRate(user) + '%' }" />
                            </div>
                        </div>
                    </Link>
                </div>

                <!-- Empty State -->
                <div v-if="!users || users.length === 0" class="text-center py-12">
                    <p class="text-gray-500 text-lg mb-4">
                        No users yet
                    </p>
                    <IconButton variant="blue" size="md" :href="route('users.create')">
                        Add Your First User
                    </IconButton>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import IconButton from '@/Components/IconButton.vue';

defineProps({
    users: Array,
    userDetails: Array,
});

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getCompletionRate = (user) => {
    if (user.tasks_assigned === 0) return 0;
    return Math.round((user.tasks_completed / user.tasks_assigned) * 100);
};
</script>

