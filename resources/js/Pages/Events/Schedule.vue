<script setup lang="ts">
import EventHeader from '@/Components/ui/EventHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ScheduleEntry } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    event: {
        title: string;
        slug: string;
        event_date: string;
        dress_code: string | null;
        latitude: number | null;
        longitude: number | null;
    };
    items: ScheduleEntry[];
}>();

const toLocalInput = (iso: string) => iso.slice(0, 16);

const itemForm = useForm({ starts_at: toLocalInput(props.event.event_date), title: '', description: '' });
const details = useForm({
    dress_code: props.event.dress_code ?? '',
    latitude: props.event.latitude as number | null,
    longitude: props.event.longitude as number | null,
});

const saved = computed(() => (usePage().props.flash as { details_saved?: boolean } | undefined)?.details_saved === true);
const fmt = (iso: string) =>
    new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));

const addItem = () => itemForm.post(route('schedule.store', props.event.slug), { onSuccess: () => itemForm.reset('title', 'description') });
const removeItem = (item: ScheduleEntry) => router.delete(route('schedule.destroy', [props.event.slug, item.id]));
const saveDetails = () => details.patch(route('events.details', props.event.slug), { preserveScroll: true });
</script>

<template>
    <Head :title="`Programme - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <EventHeader :event="event" title="Programme" current="schedule"></EventHeader>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-8 px-4 sm:px-6 lg:px-8">
                <form class="space-y-3 card p-6" @submit.prevent="saveDetails">
                    <h3 class="display text-2xl">Dress code et carte</h3>
                    <input v-model="details.dress_code" placeholder="Dress code (ex : tenue de soirée)" class="input" />
                    <div class="grid grid-cols-2 gap-3">
                        <input v-model.number="details.latitude" type="number" step="any" placeholder="Latitude" class="input !w-auto" />
                        <input v-model.number="details.longitude" type="number" step="any" placeholder="Longitude" class="input !w-auto" />
                    </div>
                    <p v-for="(msg, key) in details.errors" :key="key" class="text-sm text-red-600">{{ msg }}</p>
                    <p v-if="saved" class="text-sm text-green-700">Enregistré.</p>
                    <button type="submit" :disabled="details.processing" class="btn disabled:opacity-50">
                        Enregistrer
                    </button>
                </form>

                <form class="space-y-3 card p-6" @submit.prevent="addItem">
                    <h3 class="display text-2xl">Ajouter une étape</h3>
                    <input v-model="itemForm.starts_at" type="datetime-local" class="input" />
                    <input v-model="itemForm.title" placeholder="Titre (ex : Cérémonie)" class="input" />
                    <input v-model="itemForm.description" placeholder="Description (optionnel)" class="input" />
                    <p v-for="(msg, key) in itemForm.errors" :key="key" class="text-sm text-red-600">{{ msg }}</p>
                    <button type="submit" :disabled="itemForm.processing" class="btn">
                        Ajouter
                    </button>
                </form>

                <p v-if="items.length === 0" class="text-[var(--muted)]">Aucune étape pour le moment.</p>
                <ul v-else class="divide-y card">
                    <li v-for="item in items" :key="item.id" class="flex items-center justify-between p-4">
                        <div>
                            <p class="font-medium text-[var(--ink)]">{{ item.title }}</p>
                            <p class="text-sm text-[var(--muted)]">{{ fmt(item.starts_at) }}<span v-if="item.description"> · {{ item.description }}</span></p>
                        </div>
                        <button class="text-sm text-red-600" @click="removeItem(item)">Supprimer</button>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
