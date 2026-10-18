<script setup lang="ts">
import { computed } from 'vue';

/**
 * Pastille de statut : toujours un mot et une icône, la couleur ne porte jamais seule le sens.
 * Présence (Présent, En attente, Absent), paiement (Reçu, En attente, Échoué), modération (Approuvée, À modérer, Rejetée).
 */
const props = defineProps<{
    tone: 'success' | 'warning' | 'danger';
    label: string;
}>();

const icon = computed(() => ({ success: 'M5 13l4 4L19 7', warning: 'M12 7v5l3 2', danger: 'M6 6l12 12M18 6L6 18' })[props.tone]);
</script>

<template>
    <span class="pill" :class="`pill-${tone}`" data-testid="status-pill">
        <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle v-if="tone === 'warning'" cx="12" cy="12" r="9" />
            <path :d="icon" />
        </svg>
        {{ label }}
    </span>
</template>
