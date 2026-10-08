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

                <!-- User Profile Header -->
                <div class="bg-gray-800 rounded-lg p-8 border border-gray-700 mb-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-6">
                            <!-- Large Avatar -->
                            <div class="w-32 h-32 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-5xl font-bold">
                                <img v-if="user.avatar_url"
                                     :src="user.avatar_url"
                                     :alt="user.name"
                                     class="w-full h-full" />
                                <span v-else>{{ getInitials(user.name) }}</span>
                            </div>
                            <div>
                                <h1 class="text-4xl font-bold mb-2">
                                    {{ user.name }}
                                </h1>
                                <p class="text-gray-400 text-lg mb-4">
                                    {{ user.email }}
                                </p>
                                <IconButton variant="blue" size="sm" :href="route('users.edit', user.id)">
                                    Edit Profile
                                </IconButton>
                            </div>
                        </div>
                        <IconButton variant="red" size="sm" @click="showDeleteModal = true">
                            Delete User
                        </IconButton>
                    </div>
                </div>

                <!-- Stats Overview -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Tasks Assigned -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-lg bg-blue-600/20 flex items-center justify-center">
                                <svg class="w-8 h-8 text-blue-400"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-blue-400">
                                    {{ stats.tasks_assigned }}
                                </div>
                                <div class="text-gray-400 text-sm">
                                    Tasks Assigned
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tasks Completed -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-lg bg-green-600/20 flex items-center justify-center">
                                <svg class="w-8 h-8 text-green-400"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-green-400">
                                    {{ stats.tasks_completed }}
                                </div>
                                <div class="text-gray-400 text-sm">
                                    Tasks Completed
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Points -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-16 h-16 rounded-lg bg-yellow-600/20 flex items-center justify-center">
                                <svg class="w-8 h-8 text-yellow-400"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-yellow-400">
                                    {{ stats.total_points }}
                                </div>
                                <div class="text-gray-400 text-sm">
                                    Total Points
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Tasks -->
                <div class="bg-gray-800 rounded-lg border border-gray-700 mb-6">
                    <div class="p-6 border-b border-gray-700">
                        <h2 class="text-xl font-bold">
                            Assigned Tasks
                        </h2>
                    </div>
                    <div class="p-6">
                        <div v-if="user.chores && user.chores.length" class="space-y-3">
                            <div v-for="task in user.chores" :key="task.id" class="flex items-center justify-between p-4 bg-gray-750 rounded-lg">
                                <div class="flex-1">
                                    <div class="font-medium">
                                        {{ task.title }}
                                    </div>
                                    <div v-if="task.labels && task.labels.length" class="flex flex-wrap gap-1 mt-2">
                                        <span v-for="label in task.labels"
                                              :key="label.id"
                                              class="px-2 py-0.5 rounded text-xs"
                                              :style="{ backgroundColor: label.color + '33', color: label.color }">
                                            {{ label.name }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-lg font-semibold text-blue-400">
                                        {{ task.points }} pts
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            No tasks assigned yet
                        </div>
                    </div>
                </div>

                <!-- Recent Completions -->
                <div class="bg-gray-800 rounded-lg border border-gray-700">
                    <div class="p-6 border-b border-gray-700">
                        <h2 class="text-xl font-bold">
                            Recent Completions
                        </h2>
                    </div>
                    <div class="p-6">
                        <div v-if="recentCompletions.length" class="space-y-3">
                            <div v-for="instance in recentCompletions" :key="instance.id" class="flex items-center justify-between p-4 bg-gray-750 rounded-lg">
                                <div class="flex-1">
                                    <div class="font-medium">
                                        {{ instance.chore.title }}
                                    </div>
                                    <div class="text-sm text-gray-400 mt-1">
                                        {{ formatDate(instance.completed_at) }}
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <div class="text-lg font-semibold"
                                         :class="{
                                             'text-green-400': instance.status === 'done',
                                             'text-yellow-400': instance.status === 'skipped'
                                         }">
                                        <span v-if="instance.status === 'done'">+{{ instance.points }} pts</span>
                                        <span v-else>Skipped</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            No completions yet
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete Confirmation Modal -->
        <ConfirmModal :show="showDeleteModal"
                      title="Delete User"
                      :message="`Are you sure you want to delete ${user.name}? This cannot be undone.`"
                      confirm-text="Delete"
                      @close="showDeleteModal = false"
                      @confirm="deleteUser" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import IconButton from '@/Components/IconButton.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';

const props = defineProps({
    user: Object,
    stats: Object,
    recentCompletions: Array,
});

const showDeleteModal = ref(false);

const deleteUser = () => {
    router.delete(route('users.destroy', props.user.id));
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

