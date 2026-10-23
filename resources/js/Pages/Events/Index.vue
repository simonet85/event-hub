<script setup lang="ts">
import { formatTime, intlLocale } from '@/lib/locale';
import { translate as __ } from '@/i18n';
import EmptyState from '@/Components/ui/EmptyState.vue';
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

const fmt = (d: string) => new Intl.DateTimeFormat(intlLocale(), { dateStyle: 'long' }).format(new Date(d));
const daysLeft = (d: string) => Math.ceil((new Date(d).getTime() - Date.now()) / 86_400_000);
const typeLabel = computed<Record<string, string>>(() => ({ wedding: __('Mariage'), baptism: __('Baptême'), birthday: __('Anniversaire'), party: __('Fête'), other: __('Autre') }));

const actions = computed(() => [
    { label: __('Invités'), route: 'guests.index' },
    { label: __('Programme'), route: 'schedule.index' },
    { label: __('Cagnotte'), route: 'cagnotte.index' },
    { label: __('Galerie'), route: 'gallery.index' },
    { label: __('Modération'), route: 'moderation.index' },
]);

const restore = (slug: string) => router.post(route('events.restore', slug), {}, { preserveScroll: true });
</script>

<template>
    <Head :title="__('Mes événements')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="eyebrow">{{ __('Espace hôte') }}</p>
                    <h2 class="display mt-1 text-3xl sm:text-5xl">
                        {{ showingArchived ? __('Événements') : __('Mes') }} <em>{{ showingArchived ? __('archivés') : __('événements') }}</em>
                    </h2>
                </div>
                <Link :href="route('events.create')" class="btn">{{ __('+ Nouvel événement') }}</Link>
            </div>

            <nav class="mt-6 flex gap-2" :aria-label="__('Filtre des événements')">
                <Link
                    :href="route('events.index')"
                    class="chip"
                    :class="!showingArchived ? '!border-[var(--ink)] !bg-[var(--ink)] !text-[var(--paper)]' : ''"
                    :aria-current="!showingArchived ? 'page' : undefined"
                >
                    {{ __('Actifs') }}
                </Link>
                <Link
                    :href="route('events.index', { archived: 1 })"
                    class="chip"
                    :class="showingArchived ? '!border-[var(--ink)] !bg-[var(--ink)] !text-[var(--paper)]' : ''"
                    :aria-current="showingArchived ? 'page' : undefined"
                >
                    {{ __('Archivés (:count)', { count: archivedCount }) }}
                </Link>
            </nav>
        </template>

        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p v-if="flash?.event_archived" class="notice notice-warning mb-6" data-testid="archived-notice">
                {{ __('« :title » est archivé. Retrouvez-le dans l\'onglet « Archivés ».', { title: flash.event_archived }) }}
            </p>
            <p v-if="flash?.event_restored" class="notice notice-success mb-6" data-testid="restored-notice">
                {{ __('« :title » est de nouveau actif.', { title: flash.event_restored }) }}
            </p>

            <div v-if="events.data.length === 0" class="card mx-auto max-w-xl">
                <EmptyState v-if="showingArchived" icon="event" :title="__('Aucun événement archivé')" :text="__('Les événements que vous archivez apparaissent ici ; vous pouvez les restaurer à tout moment.')" />
                <EmptyState v-else icon="event" :title="__('Votre première célébration commence ici')" :text="__('Aucun événement pour le moment. Créez-en un, ajoutez vos invités et envoyez les invitations.')">
                    <Link :href="route('events.create')" class="btn">{{ __('Créer un événement') }}</Link>
                </EmptyState>
            </div>

            <ul v-else class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <li v-for="e in events.data" :key="e.id" class="card flex flex-col p-6" :class="showingArchived ? 'opacity-80' : ''">
                    <div class="flex items-start justify-between gap-3">
                        <span class="chip">{{ typeLabel[e.type] ?? e.type }}</span>
                        <span v-if="!showingArchived" class="eyebrow">{{ daysLeft(e.event_date) > 0 ? `${__('J-')}${daysLeft(e.event_date)}` : daysLeft(e.event_date) === 0 ? __('Aujourd\'hui') : __('Terminé') }}</span>
                        <span v-else class="chip !text-warning">{{ __('Archivé') }}</span>
                    </div>
                    <h3 class="display mt-4 text-3xl">{{ e.title }}</h3>
                    <p class="mt-1 text-sm text-[var(--muted)]">{{ fmt(e.event_date) }}</p>

                    <template v-if="!showingArchived">
                        <Link :href="route('dashboard.show', e.slug)" class="btn mt-6">{{ __('Tableau de bord') }}</Link>
                        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold">
                            <Link v-for="a in actions" :key="a.route" :href="route(a.route, e.slug)" class="text-[var(--accent)] hover:underline">{{ a.label }}</Link>
                            <Link :href="route('events.edit', e.slug)" class="text-[var(--accent)] hover:underline">{{ __('Modifier') }}</Link>
                            <a :href="route('events.public', e.slug)" target="_blank" rel="noopener" class="text-[var(--muted)] hover:underline">{{ __('Page publique ↗') }}</a>
                        </div>
                    </template>
                    <template v-else>
                        <button type="button" class="btn mt-6" @click="restore(e.slug)">{{ __('Restaurer') }}</button>
                        <div class="mt-4 flex gap-4 text-sm font-semibold">
                            <Link :href="route('dashboard.show', e.slug)" class="text-[var(--accent)] hover:underline">{{ __('Tableau de bord') }}</Link>
                            <Link :href="route('events.edit', e.slug)" class="text-[var(--accent)] hover:underline">{{ __('Modifier') }}</Link>
                        </div>
                    </template>
                </li>
            </ul>

            <nav v-if="events.links.length > 3" class="mt-8 flex flex-wrap justify-center gap-1" :aria-label="__('Pagination')">
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
