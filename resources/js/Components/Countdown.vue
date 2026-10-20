<script setup lang="ts">
import { translate as __ } from '@/i18n';
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
        { label: __('jours'), value: Math.floor(total / 86400) },
        { label: __('heures'), value: Math.floor((total % 86400) / 3600) },
        { label: __('minutes'), value: Math.floor((total % 3600) / 60) },
        { label: __('secondes'), value: total % 60 },
    ];
});
</script>

<template>
    <p v-if="finished" data-testid="countdown-finished" class="display text-3xl">{{ __('C\'est le') }} <em>{{ __('grand jour !') }}</em></p>
    <div v-else class="mx-auto grid w-full max-w-md grid-cols-4 gap-2 sm:gap-4" data-testid="countdown">
        <div v-for="part in parts" :key="part.label" class="card min-w-0 px-1 py-3 text-center sm:py-4">
            <!-- La clé relance l'animation d'apparition du chiffre à chaque changement. -->
            <div
                :key="part.value"
                class="display animate-[fade-up_0.35s_ease] text-3xl tabular-nums sm:text-5xl"
                :data-testid="`countdown-${part.label}`"
            >
                {{ String(part.value).padStart(2, '0') }}
            </div>
            <div class="eyebrow mt-1 !text-[0.6rem] sm:!text-[0.68rem]">{{ part.label }}</div>
        </div>
    </div>
</template>
