<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    event: { title: string; slug: string };
    title: string;
    current: 'dashboard' | 'guests' | 'schedule' | 'cagnotte' | 'moderation';
}>();

// `route` est un global (Ziggy) : on l'expose au gabarit via le script pour rester testable.
const url = (name: string, ...params: string[]): string => String(route(name, ...params));

const tabs = [
    { key: 'dashboard', label: 'Tableau de bord', route: 'dashboard.show' },
    { key: 'guests', label: 'Invités', route: 'guests.index' },
    { key: 'schedule', label: 'Programme', route: 'schedule.index' },
    { key: 'cagnotte', label: 'Cagnotte', route: 'cagnotte.index' },
    { key: 'moderation', label: 'Modération', route: 'moderation.index' },
] as const;
</script>

<template>
    <div>
        <Link :href="url('events.index')" class="eyebrow hover:text-[var(--accent)]">← Mes événements</Link>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm text-[var(--muted)]">{{ event.title }}</p>
                <h2 class="display text-3xl sm:text-5xl">{{ title }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <slot name="aside" />
                <a :href="url('events.public', event.slug)" target="_blank" rel="noopener" class="btn btn-ghost !px-4 !py-2 text-xs">Page publique ↗</a>
            </div>
        </div>

        <nav class="-mx-4 mt-6 overflow-x-auto px-4 sm:mx-0 sm:px-0" aria-label="Sections de l'événement">
            <ul class="flex min-w-max gap-1 border-b border-[var(--line)]">
                <li v-for="t in tabs" :key="t.key">
                    <Link
                        :href="url(t.route, event.slug)"
                        class="-mb-px block border-b-2 px-4 py-2.5 text-sm font-semibold transition"
                        :class="current === t.key ? 'border-[var(--accent)] text-[var(--accent)]' : 'border-transparent text-[var(--muted)] hover:text-[var(--ink)]'"
                        :aria-current="current === t.key ? 'page' : undefined"
                    >
                        {{ t.label }}
                    </Link>
                </li>
            </ul>
        </nav>
    </div>
</template>
