<template>
    <MainLayout>
        <div class="min-h-screen bg-gray-900 text-gray-100">
            <div class="max-w-7xl mx-auto p-6">
                <!-- Back Button -->
                <Link :href="route('chores.index')" class="inline-flex items-center gap-2 text-gray-400 hover:text-gray-200 mb-6 transition">
                    <svg class="w-5 h-5"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Chores
                </Link>

                <!-- Chore Header -->
                <div class="bg-gray-800 rounded-lg p-8 border border-gray-700 mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h1 class="text-4xl font-bold mb-2">
                                {{ chore.title }}
                            </h1>
                            <p v-if="chore.description" class="text-gray-400 text-lg">
                                {{ chore.description }}
                            </p>
                        </div>
                        <IconButton variant="blue" size="sm" :href="route('chores.edit', chore.id)">
                            Edit
                        </IconButton>
                    </div>

                    <!-- Chore Details -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <div>
                            <div class="text-sm text-gray-400 mb-1">
                                Points
                            </div>
                            <div class="text-2xl font-bold text-blue-400">
                                {{ chore.points }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-400 mb-1">
                                Schedule
                            </div>
                            <div class="text-lg">
                                {{ chore.schedule_description }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-400 mb-1">
                                Next Due
                            </div>
                            <div class="text-lg">
                                {{ chore.finished_at ? 'Done' : chore.next_due_on ? formatDate(chore.next_due_on) : 'Any time' }}
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Users & Labels -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div v-if="chore.users && chore.users.length">
                            <div class="text-sm text-gray-400 mb-2">
                                Assigned To
                            </div>
                            <div class="flex gap-2">
                                <span v-for="user in chore.users" :key="user.id" class="px-3 py-1 bg-gray-700 rounded-full text-sm">
                                    {{ user.name }}
                                </span>
                            </div>
                        </div>
                        <div v-if="chore.labels && chore.labels.length">
                            <div class="text-sm text-gray-400 mb-2">
                                Labels
                            </div>
                            <div class="flex gap-2">
                                <span v-for="label in chore.labels"
                                      :key="label.id"
                                      class="px-3 py-1 rounded-full text-sm"
                                      :style="{ backgroundColor: label.color + '44', color: label.color }">
                                    {{ label.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Section -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <!-- Total Completions -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-green-600/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-400"
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
                                    {{ analytics.total_completions }}
                                </div>
                                <div class="text-sm text-gray-400">
                                    Completions
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Skips -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-yellow-600/20 flex items-center justify-center">
                                <font-awesome-icon icon="share" class="w-6 h-6 text-yellow-400" />
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-yellow-400">
                                    {{ analytics.total_skips }}
                                </div>
                                <div class="text-sm text-gray-400">
                                    Skips
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Total Points -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-purple-600/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-purple-400"
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
                                <div class="text-3xl font-bold text-purple-400">
                                    {{ analytics.total_points_earned }}
                                </div>
                                <div class="text-sm text-gray-400">
                                    Points Earned
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Stats -->
                <div v-if="userStats && userStats.length" class="bg-gray-800 rounded-lg border border-gray-700 mb-6">
                    <div class="p-6 border-b border-gray-700">
                        <h2 class="text-xl font-bold">
                            Who's Completing This Task?
                        </h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div v-for="stat in userStats" :key="stat.user.id" class="bg-gray-750 rounded-lg p-4 text-center">
                                <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-2xl font-bold">
                                    {{ getInitials(stat.user.name) }}
                                </div>
                                <div class="font-semibold">
                                    {{ stat.user.name }}
                                </div>
                                <div class="text-2xl font-bold text-blue-400 mt-2">
                                    {{ stat.completions }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    completions
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Completion History -->
                <div class="bg-gray-800 rounded-lg border border-gray-700">
                    <div class="p-6 border-b border-gray-700">
                        <h2 class="text-xl font-bold">
                            History (Last 30 Days)
                        </h2>
                    </div>
                    <div class="p-6">
                        <div v-if="completionHistory && completionHistory.length" class="space-y-2">
                            <div v-for="completion in completionHistory" :key="completion.id" class="flex items-center justify-between p-3 bg-gray-750 rounded">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full"
                                         :class="{
                                             'bg-green-500': completion.status === 'done',
                                             'bg-yellow-500': completion.status === 'skipped'
                                         }" />
                                    <span>{{ formatDate(completion.completed_at) }}</span>
                                    <span class="text-sm text-gray-400">by {{ completion.user?.name || 'Unknown' }}</span>
                                </div>
                                <span class="text-sm font-medium"
                                      :class="{
                                          'text-green-400': completion.status === 'done',
                                          'text-yellow-400': completion.status === 'skipped'
                                      }">
                                    {{ completion.status }}
                                </span>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 text-gray-500">
                            No completion history yet
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import route from 'ziggy';
import MainLayout from '@/Layouts/MainLayout.vue';
import IconButton from '@/Components/IconButton.vue';

defineProps({
    chore: Object,
    analytics: Object,
    completionHistory: Array,
    userStats: Array,
});

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

