<script setup lang="ts">
import Countdown from '@/Components/Countdown.vue';
import type { PublicEvent } from '@/types';
import { computed } from 'vue';

const props = defineProps<{ event: PublicEvent; greeting?: string }>();

const date = computed(() => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(new Date(props.event.event_date)));
const time = computed(() => new Intl.DateTimeFormat('fr-FR', { timeStyle: 'short' }).format(new Date(props.event.event_date)));
</script>

<template>
    <header class="flex min-h-[88vh] w-full flex-col items-center justify-center py-10 text-center">
        <p class="eyebrow !text-current opacity-70">{{ event.type_label }}</p>
        <p v-if="greeting" class="mt-4 max-w-md text-lg opacity-80">{{ greeting }}</p>

        <h1 class="display my-6 max-w-3xl break-words text-6xl sm:text-8xl" style="font-family: var(--event-font)">{{ event.title }}</h1>

        <div class="flex items-center gap-4 text-sm opacity-80" aria-hidden="true">
            <span class="h-px w-12 bg-current opacity-40" />✦<span class="h-px w-12 bg-current opacity-40" />
        </div>

        <p class="mt-5 text-lg first-letter:uppercase sm:text-xl">{{ date }} · {{ time }}</p>
        <p v-if="event.location_name" class="mt-1 opacity-80">{{ event.location_name }}</p>

        <Countdown :target="event.event_date" class="mt-10" />

        <p class="eyebrow mt-14 animate-bounce !text-current opacity-50" aria-hidden="true">↓</p>
    </header>
</template>
