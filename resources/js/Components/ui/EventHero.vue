<script setup lang="ts">
import Countdown from '@/Components/Countdown.vue';
import type { PublicEvent } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    event: PublicEvent;
    /** Boutons « Confirmer ma présence » / « Voir le programme » (page d'invitation personnelle). */
    actions?: boolean;
}>();

const date = computed(() => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(new Date(props.event.event_date)));
const time = computed(() => new Intl.DateTimeFormat('fr-FR', { timeStyle: 'short' }).format(new Date(props.event.event_date)).replace(':', ' h '));
</script>

<template>
    <header id="invitation" class="flex min-h-[80vh] w-full scroll-mt-4 flex-col items-center justify-center py-12 text-center">
        <p class="eyebrow">{{ event.type_label }}</p>

        <h1 class="display-xl my-6 max-w-3xl break-words" style="font-family: var(--event-font)">{{ event.title }}</h1>

        <div class="flex items-center gap-4 text-gold" aria-hidden="true">
            <span class="h-px w-12 bg-line-strong" />✦<span class="h-px w-12 bg-line-strong" />
        </div>

        <p class="body-lg mt-5 first-letter:uppercase">{{ date }} · {{ time }}</p>
        <p v-if="event.location_name" class="mt-1 text-ink-muted">{{ event.location_name }}</p>

        <Countdown :target="event.event_date" class="mt-10" />

        <div v-if="actions" class="mt-10 flex w-full max-w-lg flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="#rsvp" class="btn whitespace-nowrap">Confirmer ma présence</a>
            <a v-if="event.schedule.length" href="#programme" class="btn btn-secondary whitespace-nowrap">Voir le programme</a>
        </div>
    </header>
</template>
