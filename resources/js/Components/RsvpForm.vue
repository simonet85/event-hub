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
        <h2 class="text-center text-xl font-semibold">
            {{ guest.rsvp_status === 'pending' ? 'Serez-vous des nôtres ?' : 'Votre réponse' }}
        </h2>

        <div class="grid grid-cols-2 gap-3">
            <label
                class="cursor-pointer rounded-lg border p-3 text-center"
                :class="accepted ? 'border-current font-semibold' : 'opacity-60'"
            >
                <input v-model="form.rsvp_status" type="radio" value="accepted" class="sr-only" />
                Je serai présent(e)
            </label>
            <label
                class="cursor-pointer rounded-lg border p-3 text-center"
                :class="!accepted ? 'border-current font-semibold' : 'opacity-60'"
            >
                <input v-model="form.rsvp_status" type="radio" value="declined" class="sr-only" />
                Je ne pourrai pas venir
            </label>
        </div>

        <template v-if="accepted">
            <label v-if="guest.max_companions > 0" class="block">
                <span class="text-sm">Accompagnants (max {{ guest.max_companions }})</span>
                <input
                    v-model.number="form.companions_count"
                    type="number"
                    min="0"
                    :max="guest.max_companions"
                    class="mt-1 block w-full rounded-md border-gray-300 text-gray-900"
                />
            </label>
            <label class="block">
                <span class="text-sm">Allergies / régime alimentaire</span>
                <input
                    v-model="form.dietary_restrictions"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 text-gray-900"
                />
            </label>
            <label class="block">
                <span class="text-sm">Une chanson pour faire danser ?</span>
                <input
                    v-model="form.favorite_song"
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 text-gray-900"
                />
            </label>
        </template>

        <label class="block">
            <span class="text-sm">Un petit mot (optionnel)</span>
            <textarea
                v-model="form.rsvp_message"
                rows="2"
                class="mt-1 block w-full rounded-md border-gray-300 text-gray-900"
            />
        </label>

        <p v-for="(msg, key) in form.errors" :key="key" class="text-sm text-red-700">{{ msg }}</p>
        <p v-if="saved" class="text-center text-sm font-medium" data-testid="rsvp-saved">
            Merci, votre réponse est enregistrée !
        </p>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full rounded-md px-4 py-2 font-semibold text-white disabled:opacity-50"
            style="background-color: var(--event-primary)"
        >
            {{ guest.rsvp_status === 'pending' ? 'Envoyer ma réponse' : 'Mettre à jour ma réponse' }}
        </button>
    </form>
</template>
