<template>
    <Teleport to="body">
        <Transition name="celebration">
            <div v-if="show" class="celebration-container">
                <!-- Confetti particles -->
                <div v-for="i in 50" 
                     :key="i" 
                     class="confetti"
                     :style="getConfettiStyle(i)" />
                
                <!-- Success message with animation -->
                <div class="celebration-message">
                    <div class="celebration-icon">🎉</div>
                    <div class="celebration-text">
                        <div class="points-earned">+{{ points }} points!</div>
                        <div class="great-job">{{ userName }}, you're crushing it!</div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch, onMounted } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false
    },
    points: {
        type: Number,
        default: 0
    },
    userName: {
        type: String,
        default: 'Great job'
    }
});

const emit = defineEmits(['complete']);

const confettiColors = ['#60A5FA', '#34D399', '#FBBF24', '#F472B6', '#A78BFA', '#FB923C'];

const getConfettiStyle = (index) => {
    const color = confettiColors[index % confettiColors.length];
    const startX = Math.random() * 100;
    const endX = startX + (Math.random() - 0.5) * 50;
    const duration = 1 + Math.random() * 1.5;
    const delay = Math.random() * 0.3;
    const rotation = Math.random() * 360;
    const size = 8 + Math.random() * 8;
    
    return {
        backgroundColor: color,
        left: `${startX}%`,
        width: `${size}px`,
        height: `${size}px`,
        animationDuration: `${duration}s`,
        animationDelay: `${delay}s`,
        '--end-x': `${endX}vw`,
        '--rotation': `${rotation}deg`
    };
};

watch(() => props.show, (newVal) => {
    if (newVal) {
        // Auto-hide after animation completes
        setTimeout(() => {
            emit('complete');
        }, 2500);
    }
});
</script>

<style scoped>
.celebration-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    pointer-events: none;
    z-index: 9999;
    overflow: hidden;
}

.confetti {
    position: absolute;
    top: -20px;
    border-radius: 50%;
    animation: confetti-fall forwards;
    opacity: 1;
}

@keyframes confetti-fall {
    0% {
        transform: translateY(0) translateX(0) rotate(0deg);
        opacity: 1;
    }
    100% {
        transform: translateY(100vh) translateX(var(--end-x)) rotate(var(--rotation));
        opacity: 0;
    }
}

.celebration-message {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    animation: celebration-bounce 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes celebration-bounce {
    0% {
        transform: translate(-50%, -50%) scale(0);
        opacity: 0;
    }
    50% {
        transform: translate(-50%, -50%) scale(1.2);
    }
    100% {
        transform: translate(-50%, -50%) scale(1);
        opacity: 1;
    }
}

.celebration-icon {
    font-size: 6rem;
    animation: icon-spin 0.6s ease-out;
    filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.3));
}

@keyframes icon-spin {
    0% {
        transform: rotate(0deg) scale(0);
    }
    100% {
        transform: rotate(360deg) scale(1);
    }
}

.celebration-text {
    text-align: center;
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.95), rgba(147, 51, 234, 0.95));
    padding: 1.5rem 3rem;
    border-radius: 1rem;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
    backdrop-filter: blur(10px);
}

.points-earned {
    font-size: 3rem;
    font-weight: bold;
    color: #FDE047;
    text-shadow: 0 2px 10px rgba(253, 224, 71, 0.5);
    margin-bottom: 0.5rem;
    animation: points-pulse 0.4s ease-out;
}

@keyframes points-pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.great-job {
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
}

.celebration-enter-active {
    transition: opacity 0.3s ease;
}

.celebration-leave-active {
    transition: opacity 0.5s ease;
}

.celebration-enter-from,
.celebration-leave-to {
    opacity: 0;
}
</style>






