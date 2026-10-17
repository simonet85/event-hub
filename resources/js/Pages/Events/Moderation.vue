<script setup lang="ts">
import EventHeader from '@/Components/ui/EventHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

type Status = 'pending' | 'approved' | 'rejected';

const props = defineProps<{
    event: { title: string; slug: string };
    entries: { id: number; author: string; message: string; status: Status }[];
    photos: { id: number; author: string; caption: string | null; status: Status; url: string }[];
}>();

const labels: Record<Status, string> = { pending: 'En attente', approved: 'Publié', rejected: 'Refusé' };

const setEntry = (id: number, status: Status) => router.patch(route('moderation.entries.update', [props.event.slug, id]), { status }, { preserveScroll: true });
const removeEntry = (id: number) => router.delete(route('moderation.entries.destroy', [props.event.slug, id]), { preserveScroll: true });
const setPhoto = (id: number, status: Status) => router.patch(route('moderation.photos.update', [props.event.slug, id]), { status }, { preserveScroll: true });
const removePhoto = (id: number) => router.delete(route('moderation.photos.destroy', [props.event.slug, id]), { preserveScroll: true });
</script>

<template>
    <Head :title="`Modération - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <EventHeader :event="event" title="Modération" current="moderation"></EventHeader>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">
                <section>
                    <h3 class="mb-3 display text-2xl">Livre d'or</h3>
                    <p v-if="entries.length === 0" class="text-[var(--muted)]">Aucun message.</p>
                    <ul v-else class="divide-y card">
                        <li v-for="e in entries" :key="e.id" class="flex flex-wrap items-start justify-between gap-3 p-4">
                            <div class="min-w-0 flex-1">
                                <p class="whitespace-pre-line text-[var(--ink)]">{{ e.message }}</p>
                                <p class="text-xs text-[var(--muted)]">{{ e.author }} · {{ labels[e.status] }}</p>
                            </div>
                            <div class="flex gap-3 text-sm">
                                <button v-if="e.status !== 'approved'" class="chip cursor-pointer !text-green-700 hover:!bg-green-50" @click="setEntry(e.id, 'approved')">Publier</button>
                                <button v-if="e.status !== 'rejected'" class="chip cursor-pointer !text-amber-700 hover:!bg-amber-50" @click="setEntry(e.id, 'rejected')">Refuser</button>
                                <button class="chip cursor-pointer !text-red-700 hover:!bg-red-50" @click="removeEntry(e.id)">Supprimer</button>
                            </div>
                        </li>
                    </ul>
                </section>

                <section>
                    <h3 class="mb-3 display text-2xl">Photos</h3>
                    <p v-if="photos.length === 0" class="text-[var(--muted)]">Aucune photo.</p>
                    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <figure v-for="p in photos" :key="p.id" class="overflow-hidden card">
                            <img :src="p.url" :alt="p.caption ?? `Photo de ${p.author}`" loading="lazy" class="aspect-square w-full object-cover" />
                            <figcaption class="space-y-2 p-3 text-sm">
                                <p class="text-[var(--muted)]">{{ p.author }} · {{ labels[p.status] }}<span v-if="p.caption"> · {{ p.caption }}</span></p>
                                <div class="flex gap-3">
                                    <button v-if="p.status !== 'approved'" class="chip cursor-pointer !text-green-700 hover:!bg-green-50" @click="setPhoto(p.id, 'approved')">Publier</button>
                                    <button v-if="p.status !== 'rejected'" class="chip cursor-pointer !text-amber-700 hover:!bg-amber-50" @click="setPhoto(p.id, 'rejected')">Refuser</button>
                                    <button class="chip cursor-pointer !text-red-700 hover:!bg-red-50" @click="removePhoto(p.id)">Supprimer</button>
                                </div>
                            </figcaption>
                        </figure>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
