<script setup lang="ts">
import PrivacyPanel from '@/Components/PrivacyPanel.vue';
import QrCode from '@/Components/QrCode.vue';
import { formatTime, intlLocale } from '@/lib/locale';
import type { PublicEvent } from '@/types';
import { computed } from 'vue';

/**
 * Onglet Billet : le QR code personnel, son numéro, le nombre de places et de quoi l'enregistrer ou l'imprimer.
 * Un invité qui a décliné n'a pas de billet ; tant qu'il n'a pas répondu, le billet annonce le maximum de places.
 */
const props = defineProps<{
    token: string;
    event: PublicEvent;
    retentionDays: number;
    guest: {
        name: string;
        url: string;
        max_companions: number;
        rsvp_status: 'pending' | 'accepted' | 'declined';
        companions_count: number;
        ticket_number: string;
        erasure_requested: boolean;
    };
}>();

defineEmits<{ rsvp: [] }>();

const date = computed(() => new Intl.DateTimeFormat(intlLocale(), { dateStyle: 'full' }).format(new Date(props.event.event_date)));
const time = computed(() => formatTime(new Date(props.event.event_date)));
const seats = computed(() => 1 + (props.guest.rsvp_status === 'accepted' ? props.guest.companions_count : props.guest.max_companions));
const declined = computed(() => props.guest.rsvp_status === 'declined');
const print = () => window.print();
const ticketUrl = computed(() => String(route('invitations.ticket', props.token)));
</script>

<template>
    <div class="mt-8 flex w-full flex-col items-center gap-8" data-testid="ticket-panel">
        <section v-if="declined" class="card w-full max-w-md space-y-4 p-8 text-center" data-testid="ticket-declined">
            <p class="eyebrow">{{ __('Billet') }}</p>
            <p>{{ __('Vous avez indiqué que vous ne pourrez pas venir : il n\'y a pas de billet.') }}</p>
            <a href="?onglet=rsvp" class="btn btn-secondary" @click.prevent="$emit('rsvp')">{{ __('Modifier ma réponse') }}</a>
        </section>

        <article v-else class="card-invitation w-full max-w-md space-y-5 p-8 text-center" data-testid="ticket-card">
            <p class="eyebrow">{{ __('Billet') }} · {{ event.type_label }}</p>
            <h2 class="display-lg" style="font-family: var(--event-font)">{{ event.title }}</h2>
            <p class="script break-words text-accent" data-testid="ticket-guest">{{ guest.name }}</p>

            <dl class="space-y-3">
                <div>
                    <dt class="eyebrow">{{ __('Date') }}</dt>
                    <dd class="body-lg mt-1 first-letter:uppercase">{{ __(':date à :time', { date, time }) }}</dd>
                </div>
                <div v-if="event.location_name">
                    <dt class="eyebrow">{{ __('Lieu') }}</dt>
                    <dd class="body-lg mt-1">{{ event.location_name }}</dd>
                </div>
                <div>
                    <dt class="eyebrow">{{ __('Places') }}</dt>
                    <dd class="body-lg mt-1" data-testid="ticket-seats">
                        {{ guest.rsvp_status === 'accepted' ? __(':count place(s)', { count: seats }) : __('Jusqu\'à :count place(s)', { count: seats }) }}
                    </dd>
                </div>
            </dl>

            <!-- Contraste maximal, même en thème sombre : encre foncée sur fond clair. -->
            <div class="mx-auto inline-block bg-white p-3">
                <QrCode :value="guest.url" :size="200" color="#2a1f17" />
            </div>
            <p class="label" data-testid="ticket-number">{{ __('N° :number', { number: guest.ticket_number }) }}</p>
            <p class="caption text-ink-muted">{{ __('Présentez ce code à l\'entrée.') }}</p>

            <div class="flex flex-col gap-3 border-t border-dashed border-line-strong pt-5 sm:flex-row sm:justify-center print:hidden">
                <a :href="ticketUrl" class="btn" data-testid="ticket-link">{{ __('Enregistrer mon billet') }}</a>
                <button type="button" class="btn btn-secondary" data-testid="ticket-print" @click="print">{{ __('Imprimer') }}</button>
            </div>
        </article>

        <div class="flex w-full justify-center print:hidden">
            <PrivacyPanel :token="token" :retention-days="retentionDays" :requested="guest.erasure_requested" />
        </div>
    </div>
</template>
