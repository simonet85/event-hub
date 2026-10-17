<script setup lang="ts">
import Countdown from '@/Components/Countdown.vue';
import EventDetails from '@/Components/EventDetails.vue';
import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import type { PublicEvent } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ event: PublicEvent }>();

const formattedDate = computed(() =>
    new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full', timeStyle: 'short' }).format(new Date(props.event.event_date)),
);
</script>

<template>
    <Head :title="event.title" />

    <EventPublicLayout :theme="event.theme_config">
        <p class="eyebrow mb-3">{{ event.type_label }}</p>
        <h1 class="display mb-8 break-words text-5xl sm:text-7xl" style="font-family: var(--event-font)">
            {{ event.title }}
        </h1>

        <Countdown :target="event.event_date" class="mb-10" />

        <section class="w-full max-w-md space-y-4 card p-6">
            <div>
                <h2 class="text-xs uppercase tracking-widest opacity-70">Date</h2>
                <p class="text-lg first-letter:uppercase">{{ formattedDate }}</p>
            </div>
            <div v-if="event.location_name">
                <h2 class="text-xs uppercase tracking-widest opacity-70">Lieu</h2>
                <p class="text-lg">{{ event.location_name }}</p>
                <p v-if="event.location_address" class="text-sm opacity-80">{{ event.location_address }}</p>
            </div>
            <p v-if="event.welcome_message" class="border-t pt-4 italic">{{ event.welcome_message }}</p>
        </section>

        <EventDetails :event="event" class="mt-8" />
    </EventPublicLayout>
</template>
