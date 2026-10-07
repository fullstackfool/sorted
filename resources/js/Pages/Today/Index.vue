<template>
    <MainLayout>
        <!-- Scoreboard -->
        <Scoreboard :users="users" />

        <template #header-actions>
            <!-- Filter Mode Buttons -->
            <div v-if="!filterMode" class="flex gap-2">
                <button class="px-4 py-2 rounded font-medium transition bg-gray-700 hover:bg-gray-600"
                        @click="filterMode = 'user'">
                    By User
                </button>
                <button class="px-4 py-2 rounded font-medium transition bg-gray-700 hover:bg-gray-600"
                        @click="filterMode = 'period'">
                    By Period
                </button>
                <button class="px-4 py-2 rounded font-medium transition bg-gray-700 hover:bg-gray-600"
                        @click="filterMode = 'label'">
                    By Label
                </button>
            </div>

            <!-- User Filter Buttons -->
            <div v-if="filterMode === 'user'" class="flex gap-2 items-center">
                <button class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded text-sm transition"
                        @click="clearFilter">
                    &#10005;
                </button>
                <button :class="[
                            'px-4 py-2 rounded font-medium transition',
                            selectedUserId === 'unassigned'
                                ? 'bg-orange-600 hover:bg-orange-700'
                                : 'bg-gray-700 hover:bg-gray-600'
                        ]"
                        @click="toggleUserFilter('unassigned')">
                    Unassigned
                </button>
                <button v-for="user in users"
                        :key="user.id"
                        :class="[
                            'px-4 py-2 rounded font-medium transition',
                            selectedUserId === user.id
                                ? 'bg-blue-600 hover:bg-blue-700'
                                : 'bg-gray-700 hover:bg-gray-600'
                        ]"
                        @click="toggleUserFilter(user.id)">
                    {{ user.name }}
                </button>
            </div>

            <!-- Period Filter Buttons -->
            <div v-if="filterMode === 'period'" class="flex gap-2 items-center">
                <button class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded text-sm transition"
                        @click="clearFilter">
                    &#10005;
                </button>
                <button v-for="period in periods"
                        :key="period.value"
                        :class="[
                            'px-4 py-2 rounded font-medium transition',
                            selectedPeriod === period.value
                                ? 'bg-purple-600 hover:bg-purple-700'
                                : 'bg-gray-700 hover:bg-gray-600'
                        ]"
                        @click="togglePeriodFilter(period.value)">
                    {{ period.label }}
                </button>
            </div>

            <!-- Label Filter Buttons -->
            <div v-if="filterMode === 'label'" class="flex gap-2 items-center">
                <button class="px-3 py-2 bg-gray-700 hover:bg-gray-600 rounded text-sm transition"
                        @click="clearFilter">
                    &#10005;
                </button>
                <button v-for="label in labels"
                        :key="label.id"
                        :class="[
                            'px-4 py-2 rounded font-medium transition',
                            selectedLabelId === label.id
                                ? 'ring-2 ring-white'
                                : ''
                        ]"
                        :style="{
                            backgroundColor: selectedLabelId === label.id ? label.color : label.color + '66',
                            color: 'white'
                        }"
                        @click="toggleLabelFilter(label.id)">
                    {{ label.name }}
                </button>
            </div>
        </template>

        <div class="min-h-screen bg-gray-900 text-gray-100">
            <div class="max-w-7xl mx-auto p-6">
                <!-- Task Groups -->
                <div v-for="group in visibleGroups"
                     :key="group.key"
                     class="mb-8">
                    <h2 class="text-2xl font-bold mb-4 flex items-center gap-2"
                        :class="group.color">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  :d="group.icon" />
                        </svg>
                        {{ group.label }}
                    </h2>

                    <div class="space-y-3">
                        <TaskCard v-for="task in group.tasks"
                                  :key="task.id"
                                  :task="task"
                                  :is-expanded="expandedTaskId === task.id"
                                  :show-date-badge="group.key === 'later'"
                                  @toggle-expand="toggleTaskExpand"
                                  @complete="(id) => showUserModal('complete', id)"
                                  @skip="(id) => showUserModal('skip', id)"
                                  @reset="resetTask" />
                    </div>
                </div>

                <!-- All Done State -->
                <div v-if="allTodayDone && !hasActiveFilter"
                     class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-green-500 mb-4"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-xl text-gray-500">
                        All done for today!
                    </p>
                </div>

                <!-- Empty Filter State -->
                <div v-else-if="hasActiveFilter && groupedTasks.today.length === 0 && groupedTasks.tomorrow.length === 0"
                     class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <p class="text-xl text-gray-500">
                        No tasks found for this filter
                    </p>
                </div>

                <!-- No Tasks At All State -->
                <div v-else-if="props.tasks.length === 0"
                     class="text-center py-16">
                    <svg class="w-16 h-16 mx-auto text-gray-600 mb-4"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p class="text-xl text-gray-500">
                        No tasks yet. Create a template to get started!
                    </p>
                </div>
            </div>
        </div>

        <!-- User Selection Modal -->
        <UserSelectionModal :show="showModal"
                            :users="users"
                            @close="closeUserModal"
                            @select="handleUserSelect" />

        <!-- Celebration Animation -->
        <CelebrationAnimation :show="showCelebration"
                              :points="celebrationPoints"
                              :user-name="celebrationUserName"
                              @complete="showCelebration = false" />
    </MainLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import route from 'ziggy';
import MainLayout from '@/Layouts/MainLayout.vue';
import UserSelectionModal from '@/Components/UserSelectionModal.vue';
import Scoreboard from '@/Components/Scoreboard.vue';
import CelebrationAnimation from '@/Components/CelebrationAnimation.vue';
import TaskCard from '@/Components/TaskCard.vue';

const props = defineProps({
    tasks: Array,
    users: Array,
    labels: Array,
});

const filterMode      = ref(null);
const selectedUserId  = ref(null);
const selectedLabelId = ref(null);
const selectedPeriod  = ref(null);

const showModal     = ref(false);
const pendingAction = ref(null);
const pendingTaskId = ref(null);

const expandedTaskId = ref(null);

// Celebration state
const showCelebration     = ref(false);
const celebrationPoints   = ref(0);
const celebrationUserName = ref('');

// Track previous allTodayDone state for super-celebration
const prevAllTodayDone = ref(false);

const periods = [
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'nodate', label: 'No Date' },
];

const groupIcons = {
    today: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    tomorrow: 'M9 5l7 7-7 7',
    later: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
};

const groupColors = {
    today: 'text-blue-400',
    tomorrow: 'text-green-400',
    later: 'text-gray-400',
};

const groupLabels = {
    today: 'Today',
    tomorrow: 'Tomorrow',
    later: 'Later',
};

const hasActiveFilter = computed(() => {
    return filterMode.value && (selectedUserId.value || selectedLabelId.value || selectedPeriod.value);
});

const filteredTasks = computed(() => {
    if (!filterMode.value) {
        return props.tasks;
    }

    if (filterMode.value === 'user' && selectedUserId.value) {
        return props.tasks.filter(task => {
            if (selectedUserId.value === 'unassigned') {
                return !task.assigned_users || task.assigned_users.length === 0;
            }
            if (!task.assigned_users || task.assigned_users.length === 0) {
                return false;
            }
            return task.assigned_users.some(user => user.id === selectedUserId.value);
        });
    }

    if (filterMode.value === 'period' && selectedPeriod.value) {
        return props.tasks.filter(task => matchesPeriod(task, selectedPeriod.value));
    }

    if (filterMode.value === 'label' && selectedLabelId.value) {
        return props.tasks.filter(task => {
            if (!task.labels || task.labels.length === 0) {
                return false;
            }
            return task.labels.some(label => label.id === selectedLabelId.value);
        });
    }

    return props.tasks;
});

const getTaskDueGroup = (task) => {
    const dateString = task.date;
    if (!dateString) return 'later';

    const date = new Date(dateString);
    date.setHours(0, 0, 0, 0);

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    if (date.getTime() === today.getTime()) return 'today';
    if (date.getTime() === tomorrow.getTime()) return 'tomorrow';
    return 'later';
};

const groupedTasks = computed(() => {
    const tasks = filteredTasks.value;

    const groups = {
        today: [],
        tomorrow: [],
        later: [],
    };

    tasks.forEach(task => {
        const group = getTaskDueGroup(task);
        groups[group].push(task);
    });

    // Sort: todo first, then done/skipped. Within each, sort by date.
    const sortTasks = (a, b) => {
        const aIsTodo = a.status === 'todo';
        const bIsTodo = b.status === 'todo';
        if (aIsTodo !== bIsTodo) return aIsTodo ? -1 : 1;

        const dateA = a.date;
        const dateB = b.date;
        if (!dateA && !dateB) return 0;
        if (!dateA) return 1;
        if (!dateB) return -1;
        return new Date(dateA).getTime() - new Date(dateB).getTime();
    };

    groups.today.sort(sortTasks);
    groups.tomorrow.sort(sortTasks);
    groups.later.sort(sortTasks);

    return groups;
});

const visibleGroups = computed(() => {
    return ['today', 'tomorrow', 'later']
        .filter(key => groupedTasks.value[key].length > 0)
        .map(key => ({
            key,
            label: groupLabels[key],
            color: groupColors[key],
            icon: groupIcons[key],
            tasks: groupedTasks.value[key],
        }));
});

// "All done" = today group has tasks but none are todo
const allTodayDone = computed(() => {
    const todayTasks = groupedTasks.value.today;
    if (todayTasks.length === 0) {
        // No tasks at all for today - check if there are any in the unfiltered source
        const allTodayTasks = props.tasks.filter(t => getTaskDueGroup(t) === 'today');
        if (allTodayTasks.length === 0) return false;
        return allTodayTasks.every(t => t.status !== 'todo');
    }
    return todayTasks.every(t => t.status !== 'todo');
});

// Super-celebration when all today tasks become done
watch(allTodayDone, (newVal, oldVal) => {
    if (newVal && !oldVal && !hasActiveFilter.value) {
        celebrationPoints.value   = 0;
        celebrationUserName.value = 'Everyone';
        showCelebration.value     = true;
    }
});

const matchesPeriod = (task, period) => {
    const now   = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

    const getStartOfWeek = (date) => {
        const d    = new Date(date);
        const day  = d.getDay();
        const diff = d.getDate() - day;
        return new Date(d.getFullYear(), d.getMonth(), diff);
    };

    const getEndOfWeek = (date) => {
        const start = getStartOfWeek(date);
        return new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6);
    };

    const getStartOfMonth = (date) => {
        return new Date(date.getFullYear(), date.getMonth(), 1);
    };

    const getEndOfMonth = (date) => {
        return new Date(date.getFullYear(), date.getMonth() + 1, 0);
    };

    const isDateInRange = (dateString, startDate, endDate) => {
        if (!dateString) return false;
        const date     = new Date(dateString);
        const dateOnly = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        return dateOnly >= startDate && dateOnly <= endDate;
    };

    if (!task.date) {
        return period === 'nodate';
    }

    if (period === 'today') {
        return isDateInRange(task.date, today, today);
    }

    if (period === 'week') {
        return isDateInRange(task.date, getStartOfWeek(today), getEndOfWeek(today));
    }

    if (period === 'month') {
        return isDateInRange(task.date, getStartOfMonth(today), getEndOfMonth(today));
    }

    if (period === 'nodate') {
        return !task.date;
    }

    return false;
};

const toggleUserFilter = (userId) => {
    selectedUserId.value = selectedUserId.value === userId ? null : userId;
};

const togglePeriodFilter = (period) => {
    selectedPeriod.value = selectedPeriod.value === period ? null : period;
};

const toggleLabelFilter = (labelId) => {
    selectedLabelId.value = selectedLabelId.value === labelId ? null : labelId;
};

const clearFilter = () => {
    filterMode.value      = null;
    selectedUserId.value  = null;
    selectedLabelId.value = null;
    selectedPeriod.value  = null;
};

const showUserModal = (action, taskId) => {
    pendingAction.value = action;
    pendingTaskId.value = taskId;
    showModal.value     = true;
};

const closeUserModal = () => {
    showModal.value     = false;
    pendingAction.value = null;
    pendingTaskId.value = null;
};

const findTask = (taskId) => {
    let task = props.tasks.find(t => t.id === taskId);
    if (!task) {
        for (const mainTask of props.tasks) {
            if (mainTask.subtasks && mainTask.subtasks.length > 0) {
                const subtask = mainTask.subtasks.find(st => st.id === taskId);
                if (subtask) {
                    task = subtask;
                    break;
                }
            }
        }
    }
    return task;
};

const handleUserSelect = (user) => {
    if (pendingAction.value === 'complete') {
        const task = findTask(pendingTaskId.value);
        if (task) {
            celebrationPoints.value   = task.points;
            celebrationUserName.value = user.name;
            showCelebration.value     = true;
        }
        completeTask(pendingTaskId.value, user.id);
    } else if (pendingAction.value === 'skip') {
        skipTask(pendingTaskId.value, user.id);
    }
    closeUserModal();
};

const completeTask = (taskId, userId) => {
    router.post(route('tasks.complete', taskId), {
        user_id: userId,
    });
};

const skipTask = (taskId, userId) => {
    router.post(route('tasks.skip', taskId), {
        user_id: userId,
    });
};

const resetTask = (taskId) => {
    router.post(route('tasks.reset', taskId));
};

const toggleTaskExpand = (taskId) => {
    expandedTaskId.value = expandedTaskId.value === taskId ? null : taskId;
};
</script>
