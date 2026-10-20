<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import EventForm, { type EventFormValues } from '@/Components/EventForm.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Theme } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    event: EventFormValues & { archived: boolean };
    types: { value: string; label: string; theme: Theme }[];
}>();

const confirming = ref(false);
const working = ref(false);

const archive = () => {
    working.value = true;
    router.post(route('events.archive', props.event.slug), {}, { onFinish: () => (working.value = false) });
};
const restore = () => router.post(route('events.restore', props.event.slug));
</script>

<template>
    <Head :title="`Modifier - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <Link :href="route('dashboard.show', event.slug)" class="eyebrow hover:text-[var(--accent)]">{{ __('← Tableau de bord') }}</Link>
            <h2 class="display mt-2 text-3xl sm:text-5xl">{{ __('Modifier l\'événement') }}</h2>
        </template>

        <div v-if="event.archived" class="mx-auto mt-6 flex max-w-7xl flex-wrap items-center justify-between gap-3 notice notice-warning sm:mx-6 lg:mx-auto" data-testid="archived-banner">
            <p>{{ __('Cet événement est archivé : ses pages invités sont masquées (404).') }}</p>
            <button type="button" class="btn !px-4 !py-2 text-xs" @click="restore">{{ __('Restaurer l\'événement') }}</button>
        </div>

        <EventForm :types="types" :event="event" />

        <section v-if="!event.archived" class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            <div class="card border-danger p-6 sm:p-8">
                <h3 class="display text-2xl">{{ __('Archiver l\'événement') }}</h3>
                <p class="mt-1 max-w-2xl text-sm text-[var(--muted)]">
                    {{ __('L\'archivage masque la page publique et les invitations aux invités. Rien n\'est supprimé : invités, réponses, photos et paiements sont conservés, et vous pouvez restaurer l\'événement à tout moment.') }}
                </p>
                <DangerButton class="mt-5" @click="confirming = true">{{ __('Archiver cet événement') }}</DangerButton>
            </div>
        </section>

        <Modal :show="confirming" @close="confirming = false">
            <div class="p-6 sm:p-8">
                <h2 class="display text-2xl">{{ __('Archiver « :title » ?', { title: event.title }) }}</h2>
                <p class="mt-2 text-sm text-[var(--muted)]">{{ __('Les invités ne pourront plus ouvrir leur invitation tant que l\'événement est archivé.') }}</p>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="confirming = false">{{ __('Annuler') }}</SecondaryButton>
                    <DangerButton :disabled="working" @click="archive">{{ __('Archiver') }}</DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
