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

                <!-- Template Form -->
                <div class="bg-gray-800 rounded-lg p-8 border border-gray-700 mb-6">
                    <h1 class="text-3xl font-bold mb-6">Create New Template</h1>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Title *</label>
                            <input v-model="form.title" type="text" required class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
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
                        
                        <div class="flex gap-2 pt-4">
                            <IconButton variant="blue" size="md" @click="saveTemplate">
                                Create Template
                            </IconButton>
                            <IconButton variant="gray" size="md" :href="route('templates.index')">
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
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import IconButton from '@/Components/IconButton.vue';

const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const form = ref({
    title: '',
    description: '',
    points: 10,
    recurrence_type: 'none',
    recurrence_pattern: null,
    deadline: null,
    flexible: false,
    days_of_week: [],
    days_of_month: [],
});

const onRecurrenceTypeChange = () => {
    // Reset recurrence pattern when type changes
    form.value.flexible = false;
    form.value.days_of_week = [];
    form.value.days_of_month = [];
    
    if (form.value.recurrence_type === 'none') {
        form.value.recurrence_pattern = null;
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
    // Validate weekly/biweekly/monthly templates need pattern
    if ((form.value.recurrence_type === 'weekly' || form.value.recurrence_type === 'biweekly') 
        && !form.value.flexible 
        && form.value.days_of_week.length === 0) {
        alert('Please select at least one day of the week or choose flexible schedule.');
        return;
    }
    
    if (form.value.recurrence_type === 'monthly' 
        && !form.value.flexible 
        && form.value.days_of_month.length === 0) {
        alert('Please select at least one day of the month or choose flexible schedule.');
        return;
    }
    
    // Build recurrence pattern
    let recurrencePattern = null;
    
    if (form.value.recurrence_type !== 'none' && form.value.recurrence_type !== 'daily') {
        if (form.value.flexible) {
            recurrencePattern = { flexible: true };
        } else if (form.value.recurrence_type === 'weekly' || form.value.recurrence_type === 'biweekly') {
            if (form.value.days_of_week.length > 0) {
                recurrencePattern = { days_of_week: form.value.days_of_week };
            }
        } else if (form.value.recurrence_type === 'monthly') {
            if (form.value.days_of_month.length > 0) {
                recurrencePattern = { days_of_month: form.value.days_of_month };
            }
        }
    }
    
    const payload = {
        title: form.value.title,
        description: form.value.description,
        points: form.value.points,
        recurrence_type: form.value.recurrence_type,
        recurrence_pattern: recurrencePattern,
        deadline: form.value.deadline,
    };
    
    router.post(route('templates.store'), payload, {
        onSuccess: () => {
            console.log('Template created successfully!');
        },
        onError: (errors) => {
            console.error('Template creation failed:', errors);
            alert('Failed to create template. Check console for errors.');
        },
    });
};
</script>

