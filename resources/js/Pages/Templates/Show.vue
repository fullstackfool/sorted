<template>
    <MainLayout>
        <div class="min-h-screen bg-gray-900 text-gray-100">
            <div class="max-w-7xl mx-auto p-6">
                <!-- Back Button -->
                <Link :href="route('templates.index')" class="inline-flex items-center gap-2 text-gray-400 hover:text-gray-200 mb-6 transition">
                    <svg class="w-5 h-5"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M15 19l-7-7 7-7" />
                    </svg>
                    Back to Templates
                </Link>

                <!-- Template Header -->
                <div class="bg-gray-800 rounded-lg p-8 border border-gray-700 mb-6">
                    <div v-if="!editMode" class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h1 class="text-4xl font-bold mb-2">
                                {{ template.title }}
                            </h1>
                            <p v-if="template.description" class="text-gray-400 text-lg">
                                {{ template.description }}
                            </p>
                        </div>
                        <IconButton variant="blue" size="sm" @click="editMode = true">
                            Edit Template
                        </IconButton>
                    </div>

                    <!-- Edit Mode -->
                    <div v-else class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Title</label>
                            <input v-model="form.title" type="text" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Points</label>
                                <input v-model.number="form.points"
                                       type="number"
                                       min="0"
                                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Recurrence Type</label>
                                <select v-model="form.recurrence_type"
                                        @change="onRecurrenceTypeChange"
                                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500">
                                    <option value="none">None (One-time)</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="biweekly">Bi-weekly (Every 2 weeks)</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                        </div>

                        <!-- Recurrence Pattern Options -->
                        <div v-if="form.recurrence_type === 'weekly' || form.recurrence_type === 'biweekly'" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-400">Weekly Schedule</label>
                            <div class="flex gap-2">
                                <label class="flex items-center gap-2 px-3 py-2 bg-gray-700 rounded cursor-pointer hover:bg-gray-600">
                                    <input type="checkbox"
                                           :checked="form.flexible"
                                           @change="toggleFlexible"
                                           class="rounded" />
                                    <span class="text-sm">Flexible (any day this period)</span>
                                </label>
                            </div>
                            <div v-if="!form.flexible" class="space-y-2">
                                <label class="block text-sm text-gray-500">Or select specific days:</label>
                                <div class="grid grid-cols-7 gap-2">
                                    <label v-for="(day, index) in daysOfWeek"
                                           :key="index"
                                           class="flex items-center justify-center px-3 py-2 rounded cursor-pointer transition"
                                           :class="form.days_of_week.includes(index) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600'">
                                        <input type="checkbox"
                                               :value="index"
                                               v-model="form.days_of_week"
                                               class="sr-only" />
                                        <span class="text-sm">{{ day }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div v-if="form.recurrence_type === 'monthly'" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-400">Monthly Schedule</label>
                            <div class="flex gap-2">
                                <label class="flex items-center gap-2 px-3 py-2 bg-gray-700 rounded cursor-pointer hover:bg-gray-600">
                                    <input type="checkbox"
                                           :checked="form.flexible"
                                           @change="toggleFlexible"
                                           class="rounded" />
                                    <span class="text-sm">Flexible (any day this month)</span>
                                </label>
                            </div>
                            <div v-if="!form.flexible" class="space-y-2">
                                <label class="block text-sm text-gray-500">Or select specific days of month:</label>
                                <div class="grid grid-cols-7 gap-2">
                                    <label v-for="day in 31"
                                           :key="day"
                                           class="flex items-center justify-center px-2 py-2 rounded cursor-pointer transition text-sm"
                                           :class="form.days_of_month.includes(day) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600'">
                                        <input type="checkbox"
                                               :value="day"
                                               v-model="form.days_of_month"
                                               class="sr-only" />
                                        <span>{{ day }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Date Fields -->
                        <div v-if="form.recurrence_type === 'none'">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Deadline (Optional)</label>
                            <input v-model="form.deadline"
                                   type="date"
                                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                        </div>
                        <div class="flex gap-2">
                            <IconButton variant="blue" size="md" @click="saveTemplate">
                                Save Changes
                            </IconButton>
                            <IconButton variant="gray" size="md" @click="cancelEdit">
                                Cancel
                            </IconButton>
                        </div>
                    </div>

                    <!-- Template Details (Read Mode) -->
                    <div v-if="!editMode" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <div>
                            <div class="text-sm text-gray-400 mb-1">
                                Points
                            </div>
                            <div class="text-2xl font-bold text-blue-400">
                                {{ template.points }}
                            </div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-400 mb-1">
                                Schedule
                            </div>
                            <div class="text-lg">
                                {{ template.recurrence_description || template.recurrence_type }}
                            </div>
                        </div>
                        <div v-if="template.deadline">
                            <div class="text-sm text-gray-400 mb-1">
                                Deadline
                            </div>
                            <div class="text-lg">
                                {{ formatDate(template.deadline) }}
                            </div>
                        </div>
                        <div v-if="template.next_due_date">
                            <div class="text-sm text-gray-400 mb-1">
                                Next Due
                            </div>
                            <div class="text-lg">
                                {{ formatDate(template.next_due_date) }}
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Users & Labels (Read Mode) -->
                    <div v-if="!editMode" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                        <div v-if="template.users && template.users.length">
                            <div class="text-sm text-gray-400 mb-2">
                                Assigned To
                            </div>
                            <div class="flex gap-2">
                                <span v-for="user in template.users" :key="user.id" class="px-3 py-1 bg-gray-700 rounded-full text-sm">
                                    {{ user.name }}
                                </span>
                            </div>
                        </div>
                        <div v-if="template.labels && template.labels.length">
                            <div class="text-sm text-gray-400 mb-2">
                                Labels
                            </div>
                            <div class="flex gap-2">
                                <span v-for="label in template.labels"
                                      :key="label.id"
                                      class="px-3 py-1 rounded-full text-sm"
                                      :style="{ backgroundColor: label.color + '44', color: label.color }">
                                    {{ label.name }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Subtemplates (Read Mode) -->
                    <div v-if="!editMode && template.subtemplates && template.subtemplates.length" class="mt-6">
                        <div class="text-sm text-gray-400 mb-2">
                            Subtemplates
                        </div>
                        <div class="space-y-2">
                            <div v-for="subtemplate in template.subtemplates" :key="subtemplate.id" class="flex items-center justify-between p-3 bg-gray-750 rounded">
                                <span>{{ subtemplate.title }}</span>
                                <span class="text-blue-400">{{ subtemplate.points }} pts</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Section -->
                <div v-if="!editMode" class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
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

                    <!-- Completion Rate -->
                    <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-lg bg-blue-600/20 flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-400"
                                     fill="none"
                                     stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                            </div>
                            <div>
                                <div class="text-3xl font-bold text-blue-400">
                                    {{ analytics.completion_rate }}%
                                </div>
                                <div class="text-sm text-gray-400">
                                    Rate
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
                <div v-if="!editMode && userStats && userStats.length" class="bg-gray-800 rounded-lg border border-gray-700 mb-6">
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
                <div v-if="!editMode" class="bg-gray-800 rounded-lg border border-gray-700">
                    <div class="p-6 border-b border-gray-700">
                        <h2 class="text-xl font-bold">
                            History (Last 30 Days)
                        </h2>
                    </div>
                    <div class="p-6">
                        <div v-if="completionHistory && completionHistory.length" class="space-y-2">
                            <div v-for="task in completionHistory" :key="task.id" class="flex items-center justify-between p-3 bg-gray-750 rounded">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full"
                                         :class="{
                                             'bg-green-500': task.status === 'done',
                                             'bg-yellow-500': task.status === 'skipped'
                                         }" />
                                    <span>{{ formatDate(task.date) }}</span>
                                    <span class="text-sm text-gray-400">by {{ task.user?.name || 'Unknown' }}</span>
                                </div>
                                <span class="text-sm font-medium"
                                      :class="{
                                          'text-green-400': task.status === 'done',
                                          'text-yellow-400': task.status === 'skipped'
                                      }">
                                    {{ task.status }}
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
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import IconButton from '@/Components/IconButton.vue';

const props = defineProps({
    template: Object,
    analytics: Object,
    completionHistory: Array,
    userStats: Array,
});

const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const editMode = ref(false);

const formatDateForInput = (dateString) => {
    if (!dateString) return null;
    const date = new Date(dateString);
    return date.toISOString().split('T')[0];
};

const initializeForm = () => {
    const pattern = props.template.recurrence_pattern || {};
    const flexible = pattern.flexible || false;
    const days_of_week = pattern.days_of_week || [];
    const days_of_month = pattern.days_of_month || [];

    return {
        title: props.template.title,
        description: props.template.description,
        points: props.template.points,
        recurrence_type: props.template.recurrence_type,
        flexible: flexible,
        days_of_week: days_of_week,
        days_of_month: days_of_month,
        deadline: props.template.deadline ? formatDateForInput(props.template.deadline) : null,
    };
};

const form = ref(initializeForm());

const onRecurrenceTypeChange = () => {
    // Reset pattern fields when type changes
    form.value.flexible = false;
    form.value.days_of_week = [];
    form.value.days_of_month = [];

    // Clear deadline if setting recurrence
    if (form.value.recurrence_type !== 'none') {
        form.value.deadline = null;
    }
};

const toggleFlexible = () => {
    form.value.flexible = !form.value.flexible;
    if (form.value.flexible) {
        form.value.days_of_week = [];
        form.value.days_of_month = [];
    }
};

const saveTemplate = () => {
    // Build recurrence_pattern
    let recurrence_pattern = null;

    if (form.value.recurrence_type !== 'none' && form.value.recurrence_type !== 'daily') {
        if (form.value.flexible) {
            recurrence_pattern = { flexible: true };
        } else if (form.value.recurrence_type === 'weekly' || form.value.recurrence_type === 'biweekly') {
            if (form.value.days_of_week.length > 0) {
                recurrence_pattern = { days_of_week: form.value.days_of_week };
            }
        } else if (form.value.recurrence_type === 'monthly') {
            if (form.value.days_of_month.length > 0) {
                recurrence_pattern = { days_of_month: form.value.days_of_month };
            }
        }
    }

    const data = {
        title: form.value.title,
        description: form.value.description,
        points: form.value.points,
        recurrence_type: form.value.recurrence_type,
        recurrence_pattern: recurrence_pattern,
        deadline: form.value.deadline,
    };

    router.put(route('templates.update', props.template.id), data, {
        onSuccess: () => {
            editMode.value = false;
        },
    });
};

const cancelEdit = () => {
    editMode.value = false;
    form.value = initializeForm();
};

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

