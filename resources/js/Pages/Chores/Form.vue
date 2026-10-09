<template>
    <MainLayout>
        <div class="min-h-screen bg-gray-900 text-gray-100">
            <div class="max-w-7xl mx-auto p-6">
                <!-- Back Button -->
                <Link :href="backUrl" class="inline-flex items-center gap-2 text-gray-400 hover:text-gray-200 mb-6 transition">
                    <svg class="w-5 h-5"
                         fill="none"
                         stroke="currentColor"
                         viewBox="0 0 24 24">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M15 19l-7-7 7-7" />
                    </svg>
                    {{ id ? 'Back to Chore' : 'Back to Chores' }}
                </Link>

                <!-- Chore Form -->
                <div class="bg-gray-800 rounded-lg p-8 border border-gray-700 mb-6">
                    <h1 class="text-3xl font-bold mb-6">{{ id ? 'Edit Chore' : 'New Chore' }}</h1>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Title *</label>
                            <input v-model="form.title" type="text" required class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                            <p v-if="form.errors.title" class="mt-1 text-sm text-red-400">{{ form.errors.title }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-400 mb-2">Description</label>
                            <textarea v-model="form.description" rows="3" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                            <p v-if="form.errors.description" class="mt-1 text-sm text-red-400">{{ form.errors.description }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Points</label>
                                <input v-model.number="form.points"
                                       type="number"
                                       min="0"
                                       class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                                <p v-if="form.errors.points" class="mt-1 text-sm text-red-400">{{ form.errors.points }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-400 mb-2">Repeats</label>
                                <select v-model="form.repeats"
                                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500">
                                    <option value="once">Once (a one-off)</option>
                                    <option value="daily">Every day</option>
                                    <option value="weekly">Weekly, on chosen days</option>
                                    <option value="monthly">Monthly, on chosen dates</option>
                                    <option value="after">After it's done</option>
                                </select>
                                <p v-if="form.errors.repeats" class="mt-1 text-sm text-red-400">{{ form.errors.repeats }}</p>
                            </div>
                        </div>

                        <!-- One-off -->
                        <div v-if="form.repeats === 'once'">
                            <label class="block text-sm font-medium text-gray-400 mb-2">Due on (optional)</label>
                            <input v-model="form.due_on"
                                   type="date"
                                   class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                            <p v-if="form.errors.due_on" class="mt-1 text-sm text-red-400">{{ form.errors.due_on }}</p>
                        </div>

                        <!-- Weekly -->
                        <div v-if="form.repeats === 'weekly'" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-400">On these days</label>
                            <div class="grid grid-cols-7 gap-2">
                                <label v-for="day in weekdays"
                                       :key="day.value"
                                       class="flex items-center justify-center px-3 py-2 rounded cursor-pointer transition"
                                       :class="form.weekdays.includes(day.value) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600'">
                                    <input type="checkbox"
                                           :value="day.value"
                                           v-model="form.weekdays"
                                           class="sr-only" />
                                    <span class="text-sm">{{ day.label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.weekdays" class="text-sm text-red-400">{{ form.errors.weekdays }}</p>
                            <label class="flex items-center gap-2 text-sm text-gray-400">
                                Every
                                <input v-model.number="form.every_weeks"
                                       type="number"
                                       min="1"
                                       max="52"
                                       class="w-20 px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                                {{ form.every_weeks === 1 ? 'week' : 'weeks' }}
                            </label>
                            <p v-if="form.errors.every_weeks" class="text-sm text-red-400">{{ form.errors.every_weeks }}</p>
                        </div>

                        <!-- Monthly -->
                        <div v-if="form.repeats === 'monthly'" class="space-y-2">
                            <label class="block text-sm font-medium text-gray-400">On these dates</label>
                            <div class="grid grid-cols-7 gap-2">
                                <label v-for="day in monthDays"
                                       :key="day.value"
                                       class="flex items-center justify-center px-2 py-2 rounded cursor-pointer transition text-sm"
                                       :class="[
                                           form.month_days.includes(day.value) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-700 hover:bg-gray-600',
                                           { 'col-span-4': day.value === -1 },
                                       ]">
                                    <input type="checkbox"
                                           :value="day.value"
                                           v-model="form.month_days"
                                           class="sr-only" />
                                    <span>{{ day.label }}</span>
                                </label>
                            </div>
                            <p v-if="form.errors.month_days" class="text-sm text-red-400">{{ form.errors.month_days }}</p>
                        </div>

                        <!-- After it's done -->
                        <div v-if="form.repeats === 'after'" class="space-y-2">
                            <div class="flex items-center gap-2 text-sm text-gray-400">
                                <input v-model.number="form.every"
                                       type="number"
                                       min="1"
                                       max="365"
                                       class="w-20 px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500" />
                                <select v-model="form.unit"
                                        class="px-3 py-2 bg-gray-700 border border-gray-600 rounded text-white focus:outline-none focus:border-blue-500">
                                    <option value="day">days</option>
                                    <option value="week">weeks</option>
                                    <option value="month">months</option>
                                </select>
                                after it's done
                            </div>
                            <p v-if="form.errors.every" class="text-sm text-red-400">{{ form.errors.every }}</p>
                            <p v-if="form.errors.unit" class="text-sm text-red-400">{{ form.errors.unit }}</p>
                        </div>

                        <div class="flex gap-2 pt-4">
                            <IconButton variant="blue" size="md" @click="save">
                                {{ id ? 'Save Chore' : 'Create Chore' }}
                            </IconButton>
                            <IconButton variant="gray" size="md" :href="backUrl">
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
import { Link, useForm } from '@inertiajs/vue3';
import route from 'ziggy';
import MainLayout from '@/Layouts/MainLayout.vue';
import IconButton from '@/Components/IconButton.vue';

const props = defineProps({
    chore: Object,
});

// Shown Monday first; the values stay 0 = Sun … 6 = Sat.
const weekdays = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 0, label: 'Sun' },
];

const monthDays = [
    ...Array.from({ length: 31 }, (_, index) => ({ value: index + 1, label: index + 1 })),
    { value: -1, label: 'Last day' },
];

// Editing passes the chore's id and its current form values; creating passes nothing.
const { id, ...values } = props.chore ?? {};

const form = useForm({
    title: '',
    description: '',
    points: 10,
    repeats: 'once',
    due_on: null,
    weekdays: [],
    every_weeks: 1,
    month_days: [],
    every: 1,
    unit: 'day',
    ...values,
});

const backUrl = id ? route('chores.show', id) : route('chores.index');

const save = () => {
    if (id) {
        form.put(route('chores.update', id));
    } else {
        form.post(route('chores.store'));
    }
};
</script>
