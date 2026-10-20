<script setup lang="ts">
import { translate as __ } from '@/i18n';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    event: { title: string; slug: string };
    title: string;
    current: 'dashboard' | 'guests' | 'schedule' | 'cagnotte' | 'moderation';
}>();

// `route` est un global (Ziggy) : on l'expose au gabarit via le script pour rester testable.
const url = (name: string, ...params: string[]): string => String(route(name, ...params));

const tabs = computed(() => [
    { key: 'dashboard', label: __('Tableau de bord'), route: 'dashboard.show' },
    { key: 'guests', label: __('Invités'), route: 'guests.index' },
    { key: 'schedule', label: __('Programme'), route: 'schedule.index' },
    { key: 'cagnotte', label: __('Cagnotte'), route: 'cagnotte.index' },
    { key: 'moderation', label: __('Modération'), route: 'moderation.index' },
]);
</script>

<template>
    <div>
        <Link :href="url('events.index')" class="eyebrow inline-flex min-h-tap items-center hover:text-[var(--accent)]">{{ __('← Mes événements') }}</Link>
        <div class="mt-2 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm text-[var(--muted)]">{{ event.title }}</p>
                <h2 class="display text-3xl sm:text-5xl">{{ title }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <slot name="aside" />
                <Link :href="url('events.edit', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">{{ __('Modifier') }}</Link>
                <a :href="url('events.public', event.slug)" target="_blank" rel="noopener" class="btn btn-ghost !px-4 !py-2 text-xs">{{ __('Page publique ↗') }}</a>
            </div>
        </div>

        <nav class="-mx-4 mt-6 overflow-x-auto px-4 sm:mx-0 sm:px-0" :aria-label="__('Sections de l\'événement')">
            <ul class="flex min-w-max gap-1 border-b border-[var(--line)]">
                <li v-for="t in tabs" :key="t.key">
                    <Link
                        :href="url(t.route, event.slug)"
                        class="-mb-px flex min-h-tap items-center border-b-2 px-4 text-sm font-semibold transition"
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
