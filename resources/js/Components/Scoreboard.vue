<template>
    <div class="bg-gray-800 border-b border-gray-700 shadow-lg">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between gap-4">
                <!-- User Scores - spread evenly across width -->
                <div class="flex items-center justify-between flex-1 gap-4">
                    <div v-for="(user, index) in sortedUsers"
                         :key="user.id"
                         class="flex-1 flex items-center justify-between px-4 py-2 rounded-lg transition-all duration-300 border"
                         :class="getUserCardClass(index)">

                        <!-- Left side - Avatar, Name and Stats -->
                        <div class="flex items-center gap-3">
                            <!-- Avatar -->
                            <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center text-sm font-bold"
                                 :class="!user.avatar_url ? getAvatarClass(index) : ''">
                                <img v-if="user.avatar_url"
                                     :src="user.avatar_url"
                                     :alt="user.name"
                                     class="w-full h-full" />
                                <span v-else>{{ getInitials(user.name) }}</span>
                            </div>

                            <!-- Name and Stats -->
                            <div class="flex flex-col">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-gray-100">{{ user.name }}</span>
                                    <!-- Streak Flame -->
                                    <span v-if="user.current_streak > 0" class="flex items-center text-orange-400">
                                        🔥<span class="text-sm font-bold">{{ user.current_streak }}</span>
                                    </span>
                                </div>
                                <div class="flex items-center gap-3 text-sm">
                                    <!-- Weekly Points -->
                                    <span class="font-bold" :class="getPointsClass(index)">
                                        {{ user.weekly_points || 0 }} pts
                                    </span>
                                    <!-- Today's Points (if any) -->
                                    <span v-if="user.today_points > 0" class="text-green-400 text-xs">
                                        +{{ user.today_points }} today
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Right side - Position Badge -->
                        <div class="text-2xl">
                            <span v-if="index === 0">👑</span>
                            <span v-else-if="index === 1">🥈</span>
                            <span v-else-if="index === 2">🥉</span>
                        </div>
                    </div>
                </div>

                <!-- Household Total - separate on the right -->
                <div class="flex-shrink-0 border-l border-gray-700 pl-6 ml-4">
                    <div class="text-center">
                        <div class="text-xs text-gray-400 uppercase mb-1">House Total</div>
                        <div class="text-2xl font-bold text-blue-400">
                            {{ totalWeeklyPoints }}
                        </div>
                        <div class="text-xs text-gray-500">this week</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
});

// Sort users by weekly points (highest first)
const sortedUsers = computed(() => {
    return [...props.users].sort((a, b) => (b.weekly_points || 0) - (a.weekly_points || 0));
});

// Calculate total household points for the week
const totalWeeklyPoints = computed(() => {
    return props.users.reduce((total, user) => total + (user.weekly_points || 0), 0);
});

// Get user initials for avatar
const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Style classes based on position
const getUserCardClass = (position) => {
    if (position === 0) return 'bg-gradient-to-r from-yellow-900/30 to-yellow-800/20 border-yellow-700/50';
    if (position === 1) return 'bg-gradient-to-r from-gray-700/30 to-gray-600/20 border-gray-600/50';
    if (position === 2) return 'bg-gradient-to-r from-orange-900/30 to-orange-800/20 border-orange-700/50';
    return 'bg-gray-750/50 border-gray-700';
};

const getAvatarClass = (position) => {
    if (position === 0) return 'bg-gradient-to-br from-yellow-500 to-yellow-600';
    if (position === 1) return 'bg-gradient-to-br from-gray-400 to-gray-500';
    if (position === 2) return 'bg-gradient-to-br from-orange-600 to-orange-700';
    return 'bg-gradient-to-br from-blue-500 to-purple-600';
};

const getPointsClass = (position) => {
    if (position === 0) return 'text-yellow-400';
    if (position === 1) return 'text-gray-300';
    if (position === 2) return 'text-orange-400';
    return 'text-blue-400';
};
</script>
