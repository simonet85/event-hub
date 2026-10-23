<script setup lang="ts">
import { formatTime, intlLocale } from '@/lib/locale';
import Countdown from '@/Components/Countdown.vue';
import type { PublicEvent } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    event: PublicEvent;
    /** Boutons « Confirmer ma présence » / « Voir le programme » (page d'invitation personnelle). */
    actions?: boolean;
}>();

defineEmits<{ rsvp: [] }>();

const date = computed(() => new Intl.DateTimeFormat(intlLocale(), { dateStyle: 'full' }).format(new Date(props.event.event_date)));
const time = computed(() => formatTime(new Date(props.event.event_date)));
</script>

<template>
    <header id="invitation" class="flex min-h-[80vh] w-full scroll-mt-4 flex-col items-center justify-center py-12 text-center">
        <p class="eyebrow">{{ event.type_label }}</p>

        <h1 class="display-xl my-6 max-w-3xl break-words" style="font-family: var(--event-font)">{{ event.title }}</h1>

        <div class="flex items-center gap-4 text-gold" aria-hidden="true">
            <span class="h-px w-12 bg-line-strong" />✦<span class="h-px w-12 bg-line-strong" />
        </div>

        <p class="body-lg mt-5"><span class="inline-block first-letter:uppercase">{{ date }}</span><span class="hidden sm:inline"> · </span><span class="block whitespace-nowrap sm:inline">{{ time }}</span></p>
        <p v-if="event.location_name" class="mt-1 text-ink-muted">{{ event.location_name }}</p>

        <Countdown :target="event.event_date" class="mt-10" />

        <div v-if="actions" class="mt-10 flex w-full max-w-lg flex-col gap-3 sm:flex-row sm:justify-center">
            <a href="?onglet=rsvp" class="btn whitespace-nowrap" @click.prevent="$emit('rsvp')">{{ __('Confirmer ma présence') }}</a>
            <a v-if="event.schedule.length" href="#programme" class="btn btn-secondary whitespace-nowrap">{{ __('Voir le programme') }}</a>
        </div>
    </header>
</template>
