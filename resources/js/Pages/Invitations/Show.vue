<script setup lang="ts">
import Contributions from '@/Components/Contributions.vue';
import Donations, { type CagnottePayload } from '@/Components/Donations.vue';
import Gallery, { type GalleryPhoto } from '@/Components/Gallery.vue';
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
import { translate as __ } from '@/i18n';
import { GUEST_TABS, resolveTab, tabUrl, type GuestTab } from '@/lib/guestTabs';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

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
        erasure_requested: boolean;
    };
    token: string;
    cagnotte: CagnottePayload | null;
    gallery: GalleryPhoto[];
    guestbook: { id: number; author: string; message: string }[];
    mine: { pending_entries: number };
    retention_days: number;
    event: PublicEvent;
}>();

const LABELS: Record<GuestTab, string> = {
    invitation: __('Invitation'),
    rsvp: __('RSVP'),
    billet: __('Billet'),
    cadeaux: __('Cadeaux'),
    galerie: __('Galerie'),
};

const tabs = computed(() => GUEST_TABS.filter((t) => t !== 'cadeaux' || props.cagnotte !== null).map((key) => ({ key, label: LABELS[key] })));
const available = computed(() => tabs.value.map((t) => t.key));
const active = ref<GuestTab>('invitation');

// Une ancre héritée (ancien e-mail) est remplacée par le paramètre d'onglet, sans ajouter d'entrée d'historique.
function syncFromUrl(canonicalize = false) {
    active.value = resolveTab(window.location.search, window.location.hash, available.value);
    if (canonicalize && window.location.hash) window.history.replaceState(window.history.state, '', tabUrl(active.value));
}

function select(tab: GuestTab) {
    if (tab === active.value) return;
    active.value = tab;
    window.history.pushState(window.history.state, '', tabUrl(tab));
    window.scrollTo({ top: 0 });
}

const onPopState = () => syncFromUrl();
onMounted(() => {
    syncFromUrl(true);
    window.addEventListener('popstate', onPopState);
});
onBeforeUnmount(() => window.removeEventListener('popstate', onPopState));
</script>

<template>
    <Head :title="`${__('Invitation')} - ${event.title}`" />

    <EventPublicLayout :theme="event.theme_config" :preset="event.theme_preset" :css-class="event.css_class" :dark="event.dark_theme">
        <EnvelopeIntro :token="token" :guest-name="guest.name" />

        <template v-if="active === 'invitation'">
            <EventHero :event="event" actions @rsvp="select('rsvp')" />

            <Reveal class="w-full">
                <InvitationCard :event="event" :guest-name="guest.name" :qr-url="guest.url" :max-companions="guest.max_companions" />
            </Reveal>

            <Reveal class="mt-12 w-full"><EventDetails :event="event" /></Reveal>
        </template>

        <div v-else-if="active === 'rsvp'" class="mt-8 flex w-full justify-center">
            <RsvpForm :token="token" :guest="guest" :retention-days="retention_days" />
        </div>

        <template v-else-if="active === 'billet'">
            <p class="mt-8 w-full text-center">
                <a :href="route('invitations.ticket', token)" class="btn-quiet btn" data-testid="ticket-link">{{ __('Ouvrir mon billet (utilisable hors ligne)') }}</a>
            </p>
            <div class="mt-12 flex w-full justify-center"><PrivacyPanel :token="token" :retention-days="retention_days" :requested="guest.erasure_requested" /></div>
        </template>

        <div v-else-if="active === 'cadeaux' && cagnotte" class="mt-8 flex w-full justify-center">
            <Donations :token="token" :cagnotte="cagnotte" />
        </div>

        <div v-else class="mt-8 flex w-full flex-col items-center gap-6">
            <Gallery :token="token" :photos="gallery" />
            <Contributions :token="token" :guestbook="guestbook" :mine="mine" />
        </div>

        <GuestNav :tabs="tabs" :active="active" @select="select" />
    </EventPublicLayout>
</template>
