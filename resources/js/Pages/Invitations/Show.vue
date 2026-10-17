<script setup lang="ts">
import Contributions from '@/Components/Contributions.vue';
import Donations, { type CagnottePayload } from '@/Components/Donations.vue';
import EventDetails from '@/Components/EventDetails.vue';
import QrCode from '@/Components/QrCode.vue';
import RsvpForm from '@/Components/RsvpForm.vue';
import EventHero from '@/Components/ui/EventHero.vue';
import Reveal from '@/Components/ui/Reveal.vue';
import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import type { PublicEvent } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
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
    cagnotte: CagnottePayload | null;
    guestbook: { id: number; author: string; message: string }[];
    photos: { id: number; author: string; caption: string | null; url: string }[];
    mine: { pending_entries: number; pending_photos: number };
    event: PublicEvent;
}>();
</script>

<template>
    <Head :title="`Invitation - ${event.title}`" />

    <EventPublicLayout :theme="event.theme_config">
        <EventHero :event="event" :greeting="`Cher(e) ${guest.name}, vous êtes invité(e) à`" />

        <Reveal class="w-full">
            <section class="card mx-auto w-full max-w-md space-y-5 p-8">
                <div v-if="event.location_name">
                    <h2 class="eyebrow">Lieu</h2>
                    <p class="display mt-1 text-2xl">{{ event.location_name }}</p>
                    <p v-if="event.location_address" class="text-sm opacity-80">{{ event.location_address }}</p>
                </div>
                <p v-if="guest.max_companions > 0" class="text-sm">
                    Vous pouvez venir avec {{ guest.max_companions }}
                    accompagnant{{ guest.max_companions > 1 ? 's' : '' }}.
                </p>
                <p v-if="event.welcome_message" class="border-t border-[var(--line)] pt-5 italic">{{ event.welcome_message }}</p>
                <div class="border-t border-dashed border-[var(--line)] pt-5">
                    <p class="eyebrow mb-3">Votre billet</p>
                    <div class="flex justify-center">
                        <QrCode :value="guest.url" :size="160" :color="event.theme_config.primary_color" />
                    </div>
                </div>
            </section>
        </Reveal>

        <Reveal class="mt-8 w-full"><EventDetails :event="event" /></Reveal>
        <Reveal class="mt-8 flex w-full justify-center"><RsvpForm :token="token" :guest="guest" /></Reveal>
        <Reveal v-if="cagnotte" class="mt-8 flex w-full justify-center"><Donations :token="token" :cagnotte="cagnotte" /></Reveal>
        <Reveal class="mt-8 flex w-full justify-center"><Contributions :token="token" :guestbook="guestbook" :photos="photos" :mine="mine" /></Reveal>
    </EventPublicLayout>
</template>
