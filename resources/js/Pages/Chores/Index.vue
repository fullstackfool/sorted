<template>
    <MainLayout>
        <template #header-actions>
            <IconButton variant="blue" size="sm" :href="route('templates.create')">
                + New Template
            </IconButton>
        </template>

        <div class="min-h-screen bg-gray-900 text-gray-100">
            <div class="max-w-7xl mx-auto p-6">
                <!-- Templates Table -->
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-750 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Template
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Assigned
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Labels
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Recurrence
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Points
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Next Due
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-400 uppercase">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <tr v-for="template in templates" :key="template.id" class="hover:bg-gray-750 transition">
                                <!-- Template Name -->
                                <td class="px-4 py-3">
                                    <div>
                                        <div class="font-medium">
                                            {{ template.title }}
                                        </div>
                                        <div v-if="template.subtemplates && template.subtemplates.length" class="text-xs text-gray-500 mt-1">
                                            {{ template.subtemplates.length }} subtemplate{{ template.subtemplates.length !== 1 ? 's' : '' }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Assigned Users -->
                                <td class="px-4 py-3">
                                    <div v-if="template.assigned_users && template.assigned_users.length" class="text-sm">
                                        {{ template.assigned_users.map(u => u.name).join(', ') }}
                                    </div>
                                    <div v-else class="text-sm text-gray-500">
                                        Unassigned
                                    </div>
                                </td>

                                <!-- Labels -->
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="label in template.labels"
                                              :key="label.id"
                                              class="px-2 py-0.5 rounded text-xs"
                                              :style="{ backgroundColor: label.color + '33', color: label.color }">
                                            {{ label.name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Recurrence -->
                                <td class="px-4 py-3">
                                    <span class="text-sm">
                                        {{ template.recurrence_description }}
                                    </span>
                                </td>

                                <!-- Points -->
                                <td class="px-4 py-3">
                                    <span class="text-sm">{{ template.points }}</span>
                                </td>

                                <!-- Next Due Date -->
                                <td class="px-4 py-3">
                                    <span v-if="template.next_due_date" class="text-sm">{{ formatDate(template.next_due_date) }}</span>
                                    <span v-else-if="template.deadline" class="text-sm text-orange-400">{{ formatDate(template.deadline) }}</span>
                                    <span v-else class="text-sm text-gray-500">—</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-right">
                                    <div class="flex gap-2 justify-end">
                                        <IconButton variant="gray"
                                                    size="md"
                                                    :href="route('templates.show', template.id)"
                                                    title="View Details">
                                            <svg class="w-5 h-5"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </IconButton>
                                        <IconButton variant="red"
                                                    size="md"
                                                    title="Delete Template"
                                                    @click="confirmDelete(template)">
                                            <svg class="w-5 h-5"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24">
                                                <path stroke-linecap="round"
                                                      stroke-linejoin="round"
                                                      stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </IconButton>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmModal :show="showDeleteModal"
                      title="Delete Template?"
                      :message="`Are you sure you want to delete '${templateToDelete?.title}'? This will also delete all associated tasks. This action cannot be undone.`"
                      confirm-text="Delete"
                      cancel-text="Cancel"
                      @close="cancelDelete"
                      @confirm="deleteTemplate" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import IconButton from '@/Components/IconButton.vue';

defineProps({
    templates: Array,
});

const showDeleteModal = ref(false);
const templateToDelete = ref(null);

const confirmDelete = (template) => {
    templateToDelete.value = template;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    templateToDelete.value = null;
};

const deleteTemplate = () => {
    if (templateToDelete.value) {
        router.delete(route('templates.destroy', templateToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                templateToDelete.value = null;
            },
            onError: (errors) => {
                console.error('Delete failed:', errors);
            },
        });
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    // Reset time parts for comparison
    today.setHours(0, 0, 0, 0);
    tomorrow.setHours(0, 0, 0, 0);
    date.setHours(0, 0, 0, 0);

    if (date.getTime() === today.getTime()) {
        return 'Today';
    } else if (date.getTime() === tomorrow.getTime()) {
        return 'Tomorrow';
    } else {
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
};
</script>

