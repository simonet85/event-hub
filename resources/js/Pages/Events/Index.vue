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
const daysLeft = (d: string) => Math.ceil((new Date(d).getTime() - Date.now()) / 86_400_000);
const typeLabel: Record<string, string> = { wedding: 'Mariage', birthday: 'Anniversaire', baptism: 'Baptême', party: 'Soirée' };

const actions = [
    { label: 'Invités', route: 'guests.index' },
    { label: 'Programme', route: 'schedule.index' },
    { label: 'Cagnotte', route: 'cagnotte.index' },
    { label: 'Modération', route: 'moderation.index' },
] as const;
</script>

<template>
    <Head title="Mes événements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="eyebrow">Espace hôte</p>
                    <h2 class="display mt-1 text-3xl sm:text-5xl">Mes <em>événements</em></h2>
                </div>
                <Link :href="route('events.create')" class="btn">+ Nouvel événement</Link>
            </div>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <div v-if="events.data.length === 0" class="card mx-auto max-w-xl p-10 text-center">
                <p class="display text-3xl">Votre première <em>célébration</em> commence ici.</p>
                <p class="mt-2 text-[var(--muted)]">Aucun événement pour le moment.</p>
                <Link :href="route('events.create')" class="btn mt-6">Créer un événement</Link>
            </div>

            <ul v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <li v-for="e in events.data" :key="e.id" class="card flex flex-col p-6">
                    <div class="flex items-start justify-between gap-3">
                        <span class="chip">{{ typeLabel[e.type] ?? e.type }}</span>
                        <span class="eyebrow">{{ daysLeft(e.event_date) > 0 ? `J-${daysLeft(e.event_date)}` : daysLeft(e.event_date) === 0 ? 'Aujourd\'hui' : 'Terminé' }}</span>
                    </div>
                    <h3 class="display mt-4 text-3xl">{{ e.title }}</h3>
                    <p class="mt-1 text-sm text-[var(--muted)]">{{ fmt(e.event_date) }}</p>

                    <Link :href="route('dashboard.show', e.slug)" class="btn mt-6">Tableau de bord</Link>
                    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold">
                        <Link v-for="a in actions" :key="a.route" :href="route(a.route, e.slug)" class="text-[var(--accent)] hover:underline">{{ a.label }}</Link>
                        <a :href="route('events.public', e.slug)" target="_blank" rel="noopener" class="text-[var(--muted)] hover:underline">Page publique ↗</a>
                    </div>
                </li>
            </ul>

            <nav v-if="events.links.length > 3" class="mt-8 flex flex-wrap justify-center gap-1" aria-label="Pagination">
                <template v-for="(l, i) in events.links" :key="i">
                    <Link
                        v-if="l.url"
                        :href="l.url"
                        class="chip"
                        :class="l.active ? '!border-[var(--ink)] !bg-[var(--ink)] !text-[var(--paper)]' : ''"
                        v-html="l.label"
                    />
                    <span v-else class="px-3 py-1 text-sm text-[var(--muted)]" v-html="l.label" />
                </template>
            </nav>
        </div>
    </AuthenticatedLayout>
</template>
