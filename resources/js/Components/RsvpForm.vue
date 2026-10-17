<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
const submit = () => form.post(route('rsvp.store', props.token), { preserveScroll: true });
</script>

<template>
    <form class="w-full max-w-md space-y-4 card p-6 text-left" @submit.prevent="submit">
        <h2 class="display text-center text-3xl">
            {{ guest.rsvp_status === 'pending' ? 'Serez-vous des nôtres ?' : 'Votre réponse' }}
        </h2>

        <div class="grid grid-cols-2 gap-3">
            <label
                class="cursor-pointer rounded-2xl border-2 p-3 text-center text-sm transition"
                :class="accepted ? 'border-current bg-[color-mix(in_srgb,currentColor_8%,transparent)] font-semibold' : 'border-[var(--line)] opacity-70 hover:opacity-100'"
            >
                <input v-model="form.rsvp_status" type="radio" value="accepted" class="sr-only" />
                Je serai présent(e)
            </label>
            <label
                class="cursor-pointer rounded-2xl border-2 p-3 text-center text-sm transition"
                :class="!accepted ? 'border-current bg-[color-mix(in_srgb,currentColor_8%,transparent)] font-semibold' : 'border-[var(--line)] opacity-70 hover:opacity-100'"
            >
                <input v-model="form.rsvp_status" type="radio" value="declined" class="sr-only" />
                Je ne pourrai pas venir
            </label>
        </div>

        <template v-if="accepted">
            <label v-if="guest.max_companions > 0" class="block">
                <span class="field-label">Accompagnants (max {{ guest.max_companions }})</span>
                <input
                    v-model.number="form.companions_count"
                    type="number"
                    min="0"
                    :max="guest.max_companions"
                    class="input"
                />
            </label>
            <label class="block">
                <span class="field-label">Allergies / régime alimentaire</span>
                <input
                    v-model="form.dietary_restrictions"
                    type="text"
                    class="input"
                />
            </label>
            <label class="block">
                <span class="field-label">Une chanson pour faire danser ?</span>
                <input
                    v-model="form.favorite_song"
                    type="text"
                    class="input"
                />
            </label>
        </template>

        <label class="block">
            <span class="field-label">Un petit mot (optionnel)</span>
            <textarea
                v-model="form.rsvp_message"
                rows="2"
                class="input"
            />
        </label>

        <p v-for="(msg, key) in form.errors" :key="key" class="text-sm text-red-700">{{ msg }}</p>
        <p v-if="saved" class="text-center text-sm font-medium" data-testid="rsvp-saved">
            Merci, votre réponse est enregistrée !
        </p>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full btn"
        >
            {{ guest.rsvp_status === 'pending' ? 'Envoyer ma réponse' : 'Mettre à jour ma réponse' }}
        </button>
    </form>
</template>
