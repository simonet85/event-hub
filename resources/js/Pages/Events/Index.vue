<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    events: {
        data: { id: number; title: string; slug: string; type: string; event_date: string }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const fmt = (d: string) => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(new Date(d));
</script>

<template>
    <Head title="Mes événements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Mes événements</h2>
                <Link
                    :href="route('events.create')"
                    class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white"
                >
                    Nouvel événement
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p v-if="events.data.length === 0" class="text-gray-600">Aucun événement pour le moment.</p>
                <ul v-else class="divide-y rounded-lg bg-white shadow-sm">
                    <li v-for="e in events.data" :key="e.id" class="flex items-center justify-between p-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ e.title }}</p>
                            <p class="text-sm text-gray-500">{{ fmt(e.event_date) }}</p>
                        </div>
                        <div class="flex gap-4 text-sm">
                            <Link :href="route('schedule.index', e.slug)" class="text-indigo-600">Programme</Link>
                            <Link :href="route('moderation.index', e.slug)" class="text-indigo-600">Modération</Link>
                            <Link :href="route('guests.index', e.slug)" class="text-indigo-600">Invités</Link>
                            <a :href="route('events.public', e.slug)" target="_blank" class="text-indigo-600">Page publique</a>
                        </div>
                    </li>
                </ul>
                <nav v-if="events.links.length > 3" class="mt-4 flex flex-wrap justify-center gap-1" aria-label="Pagination">
                    <template v-for="(l, i) in events.links" :key="i">
                        <Link
                            v-if="l.url"
                            :href="l.url"
                            class="rounded border px-3 py-1 text-sm"
                            :class="l.active ? 'bg-gray-800 text-white' : 'bg-white'"
                            v-html="l.label"
                        />
                        <span v-else class="px-3 py-1 text-sm text-gray-400" v-html="l.label" />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
