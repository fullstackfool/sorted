<template>
    <MainLayout>
        <div class="min-h-screen bg-gray-900 text-gray-100">
            <div class="max-w-7xl mx-auto p-6">
                <!-- Chores Table -->
                <div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
                    <table class="w-full">
                        <thead class="bg-gray-750 border-b border-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Chore
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Assigned
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Labels
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-400 uppercase">
                                    Schedule
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
                            <tr v-for="chore in chores" :key="chore.id" class="hover:bg-gray-750 transition">
                                <!-- Chore Name -->
                                <td class="px-4 py-3">
                                    <div>
                                        <div class="font-medium">
                                            {{ chore.title }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Assigned Users -->
                                <td class="px-4 py-3">
                                    <div v-if="chore.assigned_users && chore.assigned_users.length" class="text-sm">
                                        {{ chore.assigned_users.map(u => u.name).join(', ') }}
                                    </div>
                                    <div v-else class="text-sm text-gray-500">
                                        Unassigned
                                    </div>
                                </td>

                                <!-- Labels -->
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="label in chore.labels"
                                              :key="label.id"
                                              class="px-2 py-0.5 rounded text-xs"
                                              :style="{ backgroundColor: label.color + '33', color: label.color }">
                                            {{ label.name }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Schedule -->
                                <td class="px-4 py-3">
                                    <span class="text-sm">
                                        {{ chore.schedule_description }}
                                    </span>
                                </td>

                                <!-- Points -->
                                <td class="px-4 py-3">
                                    <span class="text-sm">{{ chore.points }}</span>
                                </td>

                                <!-- Next Due Date -->
                                <td class="px-4 py-3">
                                    <span v-if="chore.finished" class="text-sm text-green-400">Done</span>
                                    <span v-else-if="chore.next_due_on" class="text-sm">{{ formatDate(chore.next_due_on) }}</span>
                                    <span v-else class="text-sm text-gray-500">Any time</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3 text-right">
                                    <div class="flex gap-2 justify-end">
                                        <IconButton variant="gray"
                                                    size="md"
                                                    :href="route('chores.show', chore.id)"
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
                                                    title="Delete Chore"
                                                    @click="confirmDelete(chore)">
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
                      title="Delete Chore?"
                      :message="`Are you sure you want to delete '${choreToDelete?.title}'? Points already earned from it will stay on the scoreboard.`"
                      confirm-text="Delete"
                      cancel-text="Cancel"
                      @close="cancelDelete"
                      @confirm="deleteChore" />
    </MainLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import route from 'ziggy';
import MainLayout from '@/Layouts/MainLayout.vue';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import IconButton from '@/Components/IconButton.vue';

defineProps({
    chores: Array,
});

const showDeleteModal = ref(false);
const choreToDelete = ref(null);

const confirmDelete = (chore) => {
    choreToDelete.value = chore;
    showDeleteModal.value = true;
};

const cancelDelete = () => {
    showDeleteModal.value = false;
    choreToDelete.value = null;
};

const deleteChore = () => {
    if (choreToDelete.value) {
        router.delete(route('chores.destroy', choreToDelete.value.id), {
            preserveScroll: true,
            onSuccess: () => {
                showDeleteModal.value = false;
                choreToDelete.value = null;
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

