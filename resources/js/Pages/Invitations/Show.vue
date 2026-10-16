<script setup lang="ts">
import Countdown from '@/Components/Countdown.vue';
import EventDetails from '@/Components/EventDetails.vue';
import QrCode from '@/Components/QrCode.vue';
import RsvpForm from '@/Components/RsvpForm.vue';
import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import type { PublicEvent } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    guest: {
        name: string;
        max_companions: number;
        url: string;
        rsvp_status: 'pending' | 'accepted' | 'declined';
        companions_count: number;
        dietary_restrictions: string | null;
        favorite_song: string | null;
        rsvp_message: string | null;
    };
    token: string;
    event: PublicEvent;
}>();

const formattedDate = computed(() =>
    new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full', timeStyle: 'short' }).format(new Date(props.event.event_date)),
);
</script>

<template>
    <Head :title="`Invitation - ${event.title}`" />

    <EventPublicLayout :theme="event.theme_config">
        <p class="mb-2 text-sm uppercase tracking-widest opacity-70">{{ event.type_label }}</p>
        <p class="mb-2 text-lg">Cher(e) {{ guest.name }}, vous êtes invité(e) à</p>
        <h1 class="mb-8 break-words text-5xl sm:text-6xl" style="font-family: var(--event-font)">{{ event.title }}</h1>

        <Countdown :target="event.event_date" class="mb-10" />

        <section class="w-full max-w-md space-y-4 rounded-2xl bg-white/70 p-6 shadow-sm">
            <p class="text-lg capitalize">{{ formattedDate }}</p>
            <div v-if="event.location_name">
                <p class="text-lg">{{ event.location_name }}</p>
                <p v-if="event.location_address" class="text-sm opacity-80">{{ event.location_address }}</p>
            </div>
            <p v-if="guest.max_companions > 0" class="text-sm">
                Vous pouvez venir avec {{ guest.max_companions }}
                accompagnant{{ guest.max_companions > 1 ? 's' : '' }}.
            </p>
            <p v-if="event.welcome_message" class="border-t pt-4 italic">{{ event.welcome_message }}</p>
            <div class="flex justify-center border-t pt-4">
                <QrCode :value="guest.url" :size="160" :color="event.theme_config.primary_color" />
            </div>
        </section>

        <EventDetails :event="event" class="mt-8" />

        <RsvpForm :token="token" :guest="guest" class="mt-8" />
    </EventPublicLayout>
</template>
