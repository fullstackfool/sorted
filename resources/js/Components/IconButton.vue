<template>
    <component :is="href ? Link : 'button'"
               :href="href"
               :class="[
                   'px-6 py-3 rounded font-medium transition',
                   variantClasses[variant] || variantClasses.gray,
                   sizeClasses[size] || sizeClasses.md
               ]"
               :title="title"
               @click="!href ? $emit('click') : undefined">
        <slot />
    </component>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    variant: {
        type: String,
        default: 'gray',
    },
    size: {
        type: String,
        default: 'md',
    },
    href: String,
    title: String,
});

defineEmits(['click']);

const variantClasses = {
    gray: 'bg-gray-600 hover:bg-gray-700',
    blue: 'bg-blue-600 hover:bg-blue-700',
    green: 'bg-green-600 hover:bg-green-700',
    red: 'bg-red-600 hover:bg-red-700',
    yellow: 'bg-yellow-600 hover:bg-yellow-700',
};

const sizeClasses = {
    sm: 'px-3 py-2 text-sm',
    md: 'px-6 py-3',
    lg: 'px-8 py-4 text-lg',
};
</script>

