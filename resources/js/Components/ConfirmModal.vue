<template>
    <!-- Modal Overlay -->
    <div v-if="show"
         class="fixed inset-0 bg-black/70 flex items-center justify-center z-50"
         @click="closeModal">
        <!-- Modal Content -->
        <div class="bg-gray-800 rounded-lg p-8 max-w-md w-full mx-4 border border-gray-700"
             @click.stop>
            <h2 class="text-2xl font-bold mb-4">
                {{ title }}
            </h2>
            <p class="text-gray-300 mb-6">
                {{ message }}
            </p>

            <div class="flex gap-3">
                <IconButton variant="red"
                            size="md"
                            class="flex-1"
                            @click="confirm">
                    {{ confirmText || 'Delete' }}
                </IconButton>
                <IconButton variant="gray"
                            size="md"
                            class="flex-1"
                            @click="closeModal">
                    {{ cancelText || 'Cancel' }}
                </IconButton>
            </div>
        </div>
    </div>
</template>

<script setup>
import IconButton from '@/Components/IconButton.vue';

defineProps({
    show: Boolean,
    title: String,
    message: String,
    confirmText: String,
    cancelText: String,
});

const emit = defineEmits(['close', 'confirm']);

const closeModal = () => {
    emit('close');
};

const confirm = () => {
    emit('confirm');
};
</script>

