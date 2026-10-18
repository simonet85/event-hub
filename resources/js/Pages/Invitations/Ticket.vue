<script setup lang="ts">
import QrCode from '@/Components/QrCode.vue';
import StatusPill from '@/Components/ui/StatusPill.vue';
import { clearOfflineTicket, isOfflineTicketSaved, offlineSupported, saveOfflineTicket } from '@/lib/offline';
import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import type { PublicEvent } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

/**
 * Billet de l'invité : sa page minimale, utilisable sans réseau. Elle ne contient que ses propres informations
 * et celles de l'événement. Rien d'autre n'est jamais gardé sur l'appareil.
 */
const props = defineProps<{
    guest: { name: string; url: string };
    event: PublicEvent;
    ticketPath: string;
    manifestUrl: string;
}>();

const supported = offlineSupported();
const saved = ref(false);
const busy = ref(false);
const error = ref('');

const date = computed(() => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full' }).format(new Date(props.event.event_date)));
const time = computed(() => new Intl.DateTimeFormat('fr-FR', { timeStyle: 'short' }).format(new Date(props.event.event_date)).replace(':', ' h '));

const run = async (action: () => Promise<void>) => {
    busy.value = true;
    error.value = '';
    try {
        await action();
    } catch {
        error.value = 'Impossible de gérer la copie hors ligne sur cet appareil.';
    } finally {
        busy.value = false;
    }
};

const keep = () =>
    run(async () => {
        await saveOfflineTicket(props.ticketPath);
        saved.value = true;
    });

const clear = () =>
    run(async () => {
        await clearOfflineTicket();
        saved.value = false;
    });

// Une copie déjà gardée est rafraîchie à chaque visite en ligne (nouvelle version des fichiers de l'interface).
onMounted(async () => {
    saved.value = await isOfflineTicketSaved(props.ticketPath);
    if (saved.value && navigator.onLine) await run(() => saveOfflineTicket(props.ticketPath));
});
</script>

<template>
    <Head :title="`Billet - ${event.title}`">
        <link rel="manifest" :href="manifestUrl" />
    </Head>

    <EventPublicLayout :theme="event.theme_config" :preset="event.theme_preset" :css-class="event.css_class" :dark="event.dark_theme">
        <section class="my-auto w-full max-w-md space-y-6 py-10" data-testid="ticket">
            <article class="card-invitation space-y-5 p-8 text-center">
                <p class="eyebrow">Billet · {{ event.type_label }}</p>
                <h1 class="display-lg" style="font-family: var(--event-font)">{{ event.title }}</h1>
                <p class="script break-words text-accent" data-testid="ticket-guest">{{ guest.name }}</p>

                <dl class="space-y-3">
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

                <div class="mx-auto inline-block bg-white p-3">
                    <QrCode :value="guest.url" :size="220" color="#2a1f17" />
                </div>
                <p class="caption text-ink-muted">Présentez ce code à l'entrée.</p>
            </article>

            <section class="card space-y-3 p-5 text-left" aria-labelledby="offline-title" data-testid="offline-panel">
                <h2 id="offline-title" class="label">Billet hors ligne</h2>

                <template v-if="supported">
                    <p v-if="saved" class="flex items-center gap-2 text-sm" data-testid="offline-saved">
                        <StatusPill tone="success" label="Disponible hors ligne" />
                    </p>
                    <p class="caption text-ink-muted">
                        Seul ce billet est gardé sur votre appareil, avec les fichiers de l'interface : jamais les autres invités, les photos ni les paiements.
                        Sur un appareil partagé, effacez-le après l'événement.
                    </p>

                    <div class="flex flex-wrap gap-3">
                        <button v-if="!saved" type="button" class="btn" :disabled="busy" :aria-busy="busy" data-testid="offline-keep" @click="keep">Garder mon billet hors ligne</button>
                        <button v-else type="button" class="btn btn-secondary" :disabled="busy" :aria-busy="busy" data-testid="offline-clear" @click="clear">Effacer de cet appareil</button>
                    </div>
                </template>
                <p v-else class="caption text-ink-muted" data-testid="offline-unsupported">Ce navigateur ne permet pas de garder le billet hors ligne. Faites une capture d'écran du code.</p>

                <p v-if="error" class="notice notice-danger" role="alert">{{ error }}</p>
            </section>
        </section>
    </EventPublicLayout>
</template>
