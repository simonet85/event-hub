<script setup lang="ts">
import Contributions from '@/Components/Contributions.vue';
import Donations, { type CagnottePayload } from '@/Components/Donations.vue';
import EventDetails from '@/Components/EventDetails.vue';
import PrivacyPanel from '@/Components/PrivacyPanel.vue';
import RsvpForm from '@/Components/RsvpForm.vue';
import EnvelopeIntro from '@/Components/ui/EnvelopeIntro.vue';
import EventHero from '@/Components/ui/EventHero.vue';
import GuestNav from '@/Components/ui/GuestNav.vue';
import InvitationCard from '@/Components/ui/InvitationCard.vue';
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
    retention_days: number;
    event: PublicEvent;
}>();
</script>

<template>
    <Head :title="`Invitation - ${event.title}`" />

    <EventPublicLayout :theme="event.theme_config" :preset="event.theme_preset" :css-class="event.css_class" :dark="event.dark_theme">
        <EnvelopeIntro :token="token" :guest-name="guest.name" />

        <EventHero :event="event" actions />

        <Reveal class="w-full">
            <InvitationCard :event="event" :guest-name="guest.name" :qr-url="guest.url" :max-companions="guest.max_companions" />
        </Reveal>

        <p class="mt-6 w-full text-center">
            <a :href="route('invitations.ticket', token)" class="btn-quiet btn" data-testid="ticket-link">Ouvrir mon billet (utilisable hors ligne)</a>
        </p>

        <Reveal class="mt-12 w-full"><EventDetails :event="event" /></Reveal>
        <Reveal class="mt-12 flex w-full justify-center"><RsvpForm :token="token" :guest="guest" :retention-days="retention_days" /></Reveal>
        <Reveal v-if="cagnotte" class="mt-12 flex w-full justify-center"><Donations :token="token" :cagnotte="cagnotte" /></Reveal>
        <Reveal class="mt-12 flex w-full justify-center"><Contributions :token="token" :guestbook="guestbook" :photos="photos" :mine="mine" /></Reveal>

        <Reveal class="mt-12 flex w-full justify-center"><PrivacyPanel :token="token" :retention-days="retention_days" /></Reveal>

        <GuestNav with-rsvp :has-schedule="event.schedule.length > 0" :has-gifts="cagnotte !== null" />
    </EventPublicLayout>
</template>
