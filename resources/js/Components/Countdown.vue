<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ target: string }>();

const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | undefined;

onMounted(() => {
    timer = setInterval(() => (now.value = Date.now()), 1000);
});
onBeforeUnmount(() => clearInterval(timer));

const remaining = computed(() => Math.max(0, new Date(props.target).getTime() - now.value));
const finished = computed(() => remaining.value === 0);

const parts = computed(() => {
    const total = Math.floor(remaining.value / 1000);
    return [
        { label: 'jours', value: Math.floor(total / 86400) },
        { label: 'heures', value: Math.floor((total % 86400) / 3600) },
        { label: 'minutes', value: Math.floor((total % 3600) / 60) },
        { label: 'secondes', value: total % 60 },
    ];
});
</script>

<template>
    <p v-if="finished" data-testid="countdown-finished" class="text-xl">C'est le grand jour !</p>
    <div v-else class="flex justify-center gap-3 sm:gap-6" data-testid="countdown">
        <div v-for="part in parts" :key="part.label" class="min-w-[4.5rem] text-center">
            <div class="text-3xl font-semibold tabular-nums sm:text-4xl" :data-testid="`countdown-${part.label}`">
                {{ String(part.value).padStart(2, '0') }}
            </div>
            <div class="text-xs uppercase tracking-wide opacity-70">{{ part.label }}</div>
        </div>
    </div>
</template>
