<script setup lang="ts">
import QrCode from '@/Components/QrCode.vue';
import type { PublicEvent } from '@/types';
import { computed } from 'vue';

/**
 * Carte d'invitation personnalisée : noms de l'événement, nom de l'invité (le seul moment manuscrit),
 * date, lieu et QR code personnel. Seule carte du système à porter l'ombre `shadow-card`.
 */
const props = defineProps<{
    event: PublicEvent;
    guestName: string;
    qrUrl: string;
    maxCompanions?: number;
}>();

const date = computed(() => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(new Date(props.event.event_date)));
const time = computed(() => new Intl.DateTimeFormat('fr-FR', { timeStyle: 'short' }).format(new Date(props.event.event_date)).replace(':', ' h '));
</script>

<template>
    <article class="card-invitation mx-auto w-full max-w-md space-y-6 p-8 text-center" data-testid="invitation-card">
        <header>
            <p class="eyebrow">Invitation</p>
            <p class="script mt-2 break-words text-accent" data-testid="invitation-guest">{{ guestName }}</p>
            <p class="mt-2 text-ink-muted">vous invite à</p>
            <h2 class="display-lg mt-2" style="font-family: var(--event-font)">{{ event.title }}</h2>
        </header>

        <dl class="space-y-4">
            <div>
                <dt class="eyebrow">Date</dt>
                <dd class="body-lg mt-1 first-letter:uppercase">{{ date }} à {{ time }}</dd>
            </div>
            <div v-if="event.location_name">
                <dt class="eyebrow">Lieu</dt>
                <dd class="body-lg mt-1">{{ event.location_name }}</dd>
                <dd v-if="event.location_address" class="caption text-ink-muted">{{ event.location_address }}</dd>
            </div>
        </dl>

        <p v-if="maxCompanions && maxCompanions > 0" class="text-ink-muted">
            Vous pouvez venir avec {{ maxCompanions }} accompagnant{{ maxCompanions > 1 ? 's' : '' }}.
        </p>
        <p v-if="event.welcome_message" class="border-t border-line pt-5 italic">{{ event.welcome_message }}</p>

        <footer class="border-t border-dashed border-line-strong pt-5">
            <p class="eyebrow mb-3">Votre billet</p>
            <!-- Contraste maximal, même en thème sombre : encre foncée sur fond clair. -->
            <div class="mx-auto inline-block bg-white p-3">
                <QrCode :value="qrUrl" :size="160" color="#2a1f17" />
            </div>
            <p class="caption mt-2 text-ink-muted">Présentez ce code à l'entrée.</p>
        </footer>
    </article>
</template>
