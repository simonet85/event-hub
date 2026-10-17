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
                <h2 class="display text-3xl sm:text-4xl">Mes événements</h2>
                <Link
                    :href="route('events.create')"
                    class="btn"
                >
                    Nouvel événement
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <p v-if="events.data.length === 0" class="text-[var(--muted)]">Aucun événement pour le moment.</p>
                <ul v-else class="divide-y card">
                    <li v-for="e in events.data" :key="e.id" class="flex items-center justify-between p-4">
                        <div>
                            <p class="font-medium text-[var(--ink)]">{{ e.title }}</p>
                            <p class="text-sm text-[var(--muted)]">{{ fmt(e.event_date) }}</p>
                        </div>
                        <div class="flex gap-4 text-sm">
                            <Link :href="route('dashboard.show', e.slug)" class="font-semibold text-[var(--accent)] hover:underline">Tableau de bord</Link>
                            <Link :href="route('schedule.index', e.slug)" class="font-semibold text-[var(--accent)] hover:underline">Programme</Link>
                            <Link :href="route('cagnotte.index', e.slug)" class="font-semibold text-[var(--accent)] hover:underline">Cagnotte</Link>
                            <Link :href="route('moderation.index', e.slug)" class="font-semibold text-[var(--accent)] hover:underline">Modération</Link>
                            <Link :href="route('guests.index', e.slug)" class="font-semibold text-[var(--accent)] hover:underline">Invités</Link>
                            <a :href="route('events.public', e.slug)" target="_blank" class="font-semibold text-[var(--accent)] hover:underline">Page publique</a>
                        </div>
                    </li>
                </ul>
                <nav v-if="events.links.length > 3" class="mt-4 flex flex-wrap justify-center gap-1" aria-label="Pagination">
                    <template v-for="(l, i) in events.links" :key="i">
                        <Link
                            v-if="l.url"
                            :href="l.url"
                            class="rounded border px-3 py-1 text-sm"
                            :class="l.active ? 'bg-[var(--ink)] text-[var(--paper)]' : 'bg-white/70'"
                            v-html="l.label"
                        />
                        <span v-else class="px-3 py-1 text-sm text-[var(--muted)]" v-html="l.label" />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
