<script setup lang="ts">
import { celebrate, type ConfettiHandle } from '@/lib/confetti';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount } from 'vue';

const props = defineProps<{
    token: string;
    guest: {
        max_companions: number;
        rsvp_status: 'pending' | 'accepted' | 'declined';
        companions_count: number;
        dietary_restrictions: string | null;
        favorite_song: string | null;
        rsvp_message: string | null;
    };
}>();

const form = useForm({
    rsvp_status: props.guest.rsvp_status === 'pending' ? 'accepted' : props.guest.rsvp_status,
    companions_count: props.guest.companions_count,
    dietary_restrictions: props.guest.dietary_restrictions ?? '',
    favorite_song: props.guest.favorite_song ?? '',
    rsvp_message: props.guest.rsvp_message ?? '',
});

const accepted = computed(() => form.rsvp_status === 'accepted');
const saved = computed(() => (usePage().props.flash as { rsvp_saved?: boolean } | undefined)?.rsvp_saved === true);
let confetti: ConfettiHandle | null = null;
onBeforeUnmount(() => confetti?.cancel());

// Confettis seulement quand l'invité confirme sa présence (pas quand il décline), une fois la réponse enregistrée.
const submit = () =>
    form.post(route('rsvp.store', props.token), {
        preserveScroll: true,
        onSuccess: () => {
            if (form.rsvp_status === 'accepted') confetti = celebrate();
        },
    });
</script>

<template>
    <form id="rsvp" class="card w-full max-w-md scroll-mt-4 space-y-5 p-6 text-left" @submit.prevent="submit">
        <header class="text-center">
            <p class="eyebrow">Réponse</p>
            <h2 class="heading mt-1">{{ guest.rsvp_status === 'pending' ? 'Serez-vous des nôtres ?' : 'Votre réponse' }}</h2>
        </header>

        <!-- Choix segmenté : vrais contrôles (boutons radio), état sélectionné écrit en couleur d'accent, cibles de 44 px. -->
        <div class="grid grid-cols-2 border border-line-strong" role="radiogroup" aria-label="Votre présence">
            <label
                class="label flex min-h-tap cursor-pointer items-center justify-center px-2 text-center focus-within:outline focus-within:outline-2 focus-within:-outline-offset-4 focus-within:outline-line-strong"
                :class="accepted ? 'bg-accent text-on-accent' : 'bg-surface-300 text-ink'"
            >
                <input v-model="form.rsvp_status" type="radio" value="accepted" class="sr-only" />
                Je serai présent(e)
            </label>
            <label
                class="label flex min-h-tap cursor-pointer items-center justify-center border-l border-line-strong px-2 text-center focus-within:outline focus-within:outline-2 focus-within:-outline-offset-4 focus-within:outline-line-strong"
                :class="!accepted ? 'bg-accent text-on-accent' : 'bg-surface-300 text-ink'"
            >
                <input v-model="form.rsvp_status" type="radio" value="declined" class="sr-only" />
                Je ne pourrai pas venir
            </label>
        </div>

        <template v-if="accepted">
            <label v-if="guest.max_companions > 0" class="block">
                <span class="field-label">Accompagnants (max {{ guest.max_companions }})</span>
                <input v-model.number="form.companions_count" type="number" min="0" :max="guest.max_companions" class="input" />
                <span class="caption mt-1 block text-ink-muted">Votre invitation compte {{ guest.max_companions + 1 }} place{{ guest.max_companions + 1 > 1 ? 's' : '' }} au plus.</span>
            </label>
            <label class="block">
                <span class="field-label">Allergies / régime alimentaire</span>
                <input v-model="form.dietary_restrictions" type="text" class="input" />
            </label>
            <label class="block">
                <span class="field-label">Une chanson pour faire danser ?</span>
                <input v-model="form.favorite_song" type="text" class="input" />
            </label>
        </template>

        <label class="block">
            <span class="field-label">Un petit mot (optionnel)</span>
            <textarea v-model="form.rsvp_message" rows="2" class="input" />
        </label>

        <p v-for="(msg, key) in form.errors" :key="key" class="flex items-start gap-1 text-sm text-danger" role="alert">
            <span aria-hidden="true">✕</span> Erreur : {{ msg }}
        </p>

        <div v-if="saved" class="notice notice-success text-center" data-testid="rsvp-saved" role="status">
            <p class="script text-success">Merci</p>
            <p>✓ Votre réponse est enregistrée.</p>
        </div>

        <button type="submit" :disabled="form.processing" :aria-busy="form.processing" class="btn w-full">
            {{ guest.rsvp_status === 'pending' ? 'Confirmer ma présence' : 'Mettre à jour ma réponse' }}
        </button>
    </form>
</template>
