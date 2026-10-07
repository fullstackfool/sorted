<template>
    <div class="bg-gray-800 rounded-lg border border-gray-700 transition"
         :class="isExpanded ? 'border-blue-500' : 'hover:border-gray-600'">
        <!-- Main Task Row -->
        <div class="flex items-center gap-4 p-4 cursor-pointer" @click="$emit('toggle-expand', task.id)">
            <!-- Task Info -->
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-3 mb-2">
                    <Link :href="route('templates.show', task.template_id)"
                          class="text-lg font-semibold hover:text-blue-400 transition cursor-pointer"
                          :class="statusClass"
                          @click.stop>
                        {{ task.title }}
                    </Link>
                    <span v-if="task.subtasks && task.subtasks.length"
                          class="text-xs text-gray-400">
                        ({{ completedSubtasksCount }}/{{ task.subtasks.length }} subtasks)
                    </span>
                </div>

                <div class="flex items-center gap-4 text-sm">
                    <!-- Assigned Users or Completed By -->
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-gray-500"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span v-if="task.status === 'done' && task.completed_by"
                              class="text-green-400 flex items-center gap-1">
                            <span>&#10003;</span>
                            <span>{{ task.completed_by.name }}</span>
                        </span>
                        <span v-else-if="task.status === 'skipped' && task.completed_by"
                              class="text-yellow-400 flex items-center gap-1">
                            <font-awesome-icon icon="share" class="w-3 h-3" />
                            <span>{{ task.completed_by.name }}</span>
                        </span>
                        <span v-else-if="task.assigned_users && task.assigned_users.length"
                              class="text-gray-300">
                            {{ task.assigned_users.map(u => u.name).join(', ') }}
                        </span>
                        <span v-else class="text-gray-500 italic">Unassigned</span>
                    </div>

                    <!-- Labels -->
                    <div v-if="task.labels && task.labels.length" class="flex gap-1">
                        <span v-for="label in task.labels"
                              :key="label.id"
                              class="px-2 py-0.5 rounded text-xs"
                              :style="{ backgroundColor: label.color + '33', color: label.color }">
                            {{ label.name }}
                        </span>
                    </div>

                    <!-- Due Date Badge (only for "later" group) -->
                    <span v-if="showDateBadge && task.date"
                          class="inline-flex items-center gap-1 text-xs text-blue-400">
                        <svg class="w-3 h-3"
                             fill="none"
                             stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round"
                                  stroke-linejoin="round"
                                  stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ formatDateShort(task.date) }}
                    </span>
                </div>
            </div>

            <!-- Points -->
            <div class="text-center px-4">
                <div class="text-2xl font-bold text-blue-400">
                    {{ task.points }}
                </div>
                <div class="text-xs text-gray-500">
                    pts
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2" @click.stop>
                <IconButton v-if="task.status === 'todo'"
                            variant="gray"
                            :title="isExpanded ? 'Collapse' : 'Expand Details'"
                            @click="$emit('toggle-expand', task.id)">
                    <svg class="w-5 h-5 transition-transform"
                         :class="{ 'rotate-180': isExpanded }"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M19 9l-7 7-7-7" />
                    </svg>
                </IconButton>
                <IconButton v-if="task.status === 'todo'"
                            variant="gray"
                            title="Skip"
                            @click="$emit('skip', task.id)">
                    <font-awesome-icon icon="share" class="w-5 h-5" />
                </IconButton>
                <IconButton v-if="task.status === 'todo'"
                            variant="green"
                            title="Complete"
                            @click="$emit('complete', task.id)">
                    &#10003;
                </IconButton>
                <IconButton v-if="task.status !== 'todo'"
                            variant="blue"
                            title="Reset"
                            @click="$emit('reset', task.id)">
                    &#8634;
                </IconButton>
            </div>
        </div>

        <!-- Expanded Details -->
        <div v-if="isExpanded" class="border-t border-gray-700 p-6 bg-gray-750">
            <!-- Description -->
            <div v-if="task.description" class="mb-6">
                <h4 class="text-sm font-semibold text-gray-400 uppercase mb-2">
                    Description
                </h4>
                <p class="text-gray-300">
                    {{ task.description }}
                </p>
            </div>

            <!-- Task Details Grid -->
            <div class="mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <!-- Recurrence -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-400 uppercase mb-2">
                            Schedule
                        </h4>
                        <p class="text-gray-300 flex items-center gap-2">
                            <svg v-if="task.recurrence_type !== 'none'"
                                 class="w-4 h-4 text-blue-400"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>{{
                                    task.recurrence_description || (task.recurrence_type === 'none' ? 'One-time task' : task.recurrence_type)
                                  }}</span>
                        </p>
                    </div>

                    <!-- Points -->
                    <div>
                        <h4 class="text-sm font-semibold text-gray-400 uppercase mb-2">
                            Points Value
                        </h4>
                        <p class="text-gray-300 flex items-center gap-2">
                            <svg class="w-4 h-4 text-yellow-400"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                            <span>{{ task.points }} points</span>
                        </p>
                    </div>

                    <!-- Due Date -->
                    <div v-if="task.date">
                        <h4 class="text-sm font-semibold text-gray-400 uppercase mb-2">
                            Due Date
                        </h4>
                        <p class="text-orange-400 flex items-center gap-2">
                            <svg class="w-4 h-4"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round"
                                      stroke-linejoin="round"
                                      stroke-width="2"
                                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ formatDate(task.date) }}</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Subtasks -->
            <div v-if="task.subtasks && task.subtasks.length">
                <div class="flex items-center gap-3 mb-3">
                    <h4 class="text-sm font-semibold text-gray-400 uppercase whitespace-nowrap">
                        Subtasks
                    </h4>
                    <div class="flex-1 h-2 bg-gray-700 rounded-full overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-blue-500 to-green-500 transition-all duration-300"
                             :style="{ width: `${(completedSubtasksCount / task.subtasks.length) * 100}%` }">
                        </div>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap">
                        {{ completedSubtasksCount }}/{{ task.subtasks.length }}
                    </span>
                </div>
                <div class="space-y-2">
                    <div v-for="subtask in task.subtasks"
                         :key="subtask.id"
                         class="flex items-center justify-between p-3 bg-gray-800 rounded-lg border border-gray-700">
                        <div class="flex items-center gap-3 flex-1">
                            <div class="w-2 h-2 rounded-full"
                                 :class="{
                                 'bg-green-500': subtask.status === 'done',
                                 'bg-yellow-500': subtask.status === 'skipped',
                                 'bg-blue-500': subtask.status === 'todo'
                             }" />
                            <span class="font-medium" :class="getStatusClass(subtask.status)">
                                {{ subtask.title }}
                            </span>
                            <span v-if="subtask.status !== 'todo' && subtask.completed_by"
                                  class="text-xs"
                                  :class="{
                                  'text-green-400': subtask.status === 'done',
                                  'text-yellow-400': subtask.status === 'skipped'
                              }">
                                by {{ subtask.completed_by.name }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-blue-400 font-semibold">{{ subtask.points }} pts</span>
                            <div v-if="subtask.status === 'todo'" class="flex gap-2">
                                <IconButton variant="gray"
                                            size="sm"
                                            title="Skip Subtask"
                                            @click="$emit('skip', subtask.id)">
                                    <font-awesome-icon icon="share" class="w-3 h-3" />
                                </IconButton>
                                <IconButton variant="green"
                                            size="sm"
                                            title="Complete Subtask"
                                            @click="$emit('complete', subtask.id)">
                                    &#10003;
                                </IconButton>
                            </div>
                            <IconButton v-else
                                        variant="blue"
                                        size="sm"
                                        title="Reset"
                                        @click="$emit('reset', subtask.id)">
                                &#8634;
                            </IconButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import route from 'ziggy';
import IconButton from '@/Components/IconButton.vue';

const props = defineProps({
    task: {
        type: Object,
        required: true,
    },
    isExpanded: {
        type: Boolean,
        default: false,
    },
    showDateBadge: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['toggle-expand', 'complete', 'skip', 'reset']);

const statusClass = computed(() => getStatusClass(props.task.status));

const completedSubtasksCount = computed(() => {
    if (!props.task.subtasks) return 0;
    return props.task.subtasks.filter(st => st.status === 'done' || st.status === 'skipped').length;
});

const getStatusClass = (status) => {
    if (status === 'done') return 'line-through text-gray-500';
    if (status === 'skipped') return 'line-through text-gray-600';
    return '';
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};

const formatDateShort = (dateString) => {
    const date = new Date(dateString);
    date.setHours(0, 0, 0, 0);

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const diffTime = date.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays < 0) {
        const absDays = Math.abs(diffDays);
        if (absDays === 1) return 'Yesterday';
        if (absDays < 7) return `${absDays} days ago`;
        const weeks = Math.floor(absDays / 7);
        if (weeks === 1) return 'Last week';
        return `${weeks} weeks ago`;
    }

    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Tomorrow';
    if (diffDays < 7) return `in ${diffDays} days`;
    if (diffDays < 14) return 'Next week';

    const weeks = Math.ceil(diffDays / 7);
    return `in ${weeks} weeks`;
};
</script>
