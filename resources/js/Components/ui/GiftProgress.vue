<script setup lang="ts">
import { fcfa } from '@/lib/format';
import { computed } from 'vue';

/**
 * Jauge de cagnotte ou de cadeau : montant en grand, barre `accent` sur `surface-300`,
 * pourcentage toujours écrit (jamais la couleur seule).
 */
const props = defineProps<{
    raised: number;
    goal?: number | null;
    contributors?: number;
    label?: string;
}>();

const percent = computed(() => (props.goal && props.goal > 0 ? Math.min(100, Math.round((props.raised / props.goal) * 100)) : null));
</script>

<template>
    <div data-testid="gift-progress">
        <p v-if="label" class="eyebrow">{{ label }}</p>
        <p class="display-lg" data-testid="gift-raised">{{ fcfa(raised) }}</p>

        <template v-if="percent !== null">
            <div
                class="mt-3 h-2 overflow-hidden bg-surface-300"
                role="progressbar"
                :aria-valuenow="percent"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-label="__(':percent % de l\'objectif', { percent })"
            >
                <div class="h-full bg-accent" :style="{ width: `${percent}%` }" />
            </div>
            <p class="caption mt-2 flex justify-between text-ink-muted">
                <span data-testid="gift-percent">{{ __(':percent % de l\'objectif', { percent }) }}</span>
                <span>{{ __('objectif :amount', { amount: fcfa(goal as number) }) }}</span>
            </p>
        </template>
        <p v-if="contributors !== undefined" class="caption mt-1 text-ink-muted" data-testid="gift-contributors">
            {{ contributors > 1 ? __(':count participants', { count: contributors }) : __(':count participant', { count: contributors }) }}
        </p>
    </div>
</template>
