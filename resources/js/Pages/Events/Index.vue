<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    events: {
        data: { id: number; title: string; slug: string; type: string; event_date: string }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    showingArchived: boolean;
    archivedCount: number;
}>();

const flash = computed(() => usePage().props.flash as { event_archived?: string | null; event_restored?: string | null } | undefined);

const fmt = (d: string) => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(new Date(d));
const daysLeft = (d: string) => Math.ceil((new Date(d).getTime() - Date.now()) / 86_400_000);
const typeLabel: Record<string, string> = { wedding: 'Mariage', baptism: 'Baptême', birthday: 'Anniversaire', party: 'Fête', other: 'Autre' };

const actions = [
    { label: 'Invités', route: 'guests.index' },
    { label: 'Programme', route: 'schedule.index' },
    { label: 'Cagnotte', route: 'cagnotte.index' },
    { label: 'Modération', route: 'moderation.index' },
] as const;

const restore = (slug: string) => router.post(route('events.restore', slug), {}, { preserveScroll: true });
</script>

<template>
    <Head title="Mes événements" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="eyebrow">Espace hôte</p>
                    <h2 class="display mt-1 text-3xl sm:text-5xl">
                        {{ showingArchived ? 'Événements' : 'Mes' }} <em>{{ showingArchived ? 'archivés' : 'événements' }}</em>
                    </h2>
                </div>
                <Link :href="route('events.create')" class="btn">+ Nouvel événement</Link>
            </div>

            <nav class="mt-6 flex gap-2" aria-label="Filtre des événements">
                <Link
                    :href="route('events.index')"
                    class="chip"
                    :class="!showingArchived ? '!border-[var(--ink)] !bg-[var(--ink)] !text-[var(--paper)]' : ''"
                    :aria-current="!showingArchived ? 'page' : undefined"
                >
                    Actifs
                </Link>
                <Link
                    :href="route('events.index', { archived: 1 })"
                    class="chip"
                    :class="showingArchived ? '!border-[var(--ink)] !bg-[var(--ink)] !text-[var(--paper)]' : ''"
                    :aria-current="showingArchived ? 'page' : undefined"
                >
                    Archivés ({{ archivedCount }})
                </Link>
            </nav>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p v-if="flash?.event_archived" class="notice notice-warning mb-6" data-testid="archived-notice">
                « {{ flash.event_archived }} » est archivé. Retrouvez-le dans l'onglet « Archivés ».
            </p>
            <p v-if="flash?.event_restored" class="notice notice-success mb-6" data-testid="restored-notice">
                « {{ flash.event_restored }} » est de nouveau actif.
            </p>

            <div v-if="events.data.length === 0" class="card mx-auto max-w-xl p-10 text-center">
                <template v-if="showingArchived">
                    <p class="display text-3xl">Aucun événement <em>archivé</em>.</p>
                </template>
                <template v-else>
                    <p class="display text-3xl">Votre première <em>célébration</em> commence ici.</p>
                    <p class="mt-2 text-[var(--muted)]">Aucun événement pour le moment.</p>
                    <Link :href="route('events.create')" class="btn mt-6">Créer un événement</Link>
                </template>
            </div>

            <ul v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <li v-for="e in events.data" :key="e.id" class="card flex flex-col p-6" :class="showingArchived ? 'opacity-80' : ''">
                    <div class="flex items-start justify-between gap-3">
                        <span class="chip">{{ typeLabel[e.type] ?? e.type }}</span>
                        <span v-if="!showingArchived" class="eyebrow">{{ daysLeft(e.event_date) > 0 ? `J-${daysLeft(e.event_date)}` : daysLeft(e.event_date) === 0 ? "Aujourd'hui" : 'Terminé' }}</span>
                        <span v-else class="chip !text-warning">Archivé</span>
                    </div>
                    <h3 class="display mt-4 text-3xl">{{ e.title }}</h3>
                    <p class="mt-1 text-sm text-[var(--muted)]">{{ fmt(e.event_date) }}</p>

                    <template v-if="!showingArchived">
                        <Link :href="route('dashboard.show', e.slug)" class="btn mt-6">Tableau de bord</Link>
                        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold">
                            <Link v-for="a in actions" :key="a.route" :href="route(a.route, e.slug)" class="text-[var(--accent)] hover:underline">{{ a.label }}</Link>
                            <Link :href="route('events.edit', e.slug)" class="text-[var(--accent)] hover:underline">Modifier</Link>
                            <a :href="route('events.public', e.slug)" target="_blank" rel="noopener" class="text-[var(--muted)] hover:underline">Page publique ↗</a>
                        </div>
                    </template>
                    <template v-else>
                        <button type="button" class="btn mt-6" @click="restore(e.slug)">Restaurer</button>
                        <div class="mt-4 flex gap-4 text-sm font-semibold">
                            <Link :href="route('dashboard.show', e.slug)" class="text-[var(--accent)] hover:underline">Tableau de bord</Link>
                            <Link :href="route('events.edit', e.slug)" class="text-[var(--accent)] hover:underline">Modifier</Link>
                        </div>
                    </template>
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
