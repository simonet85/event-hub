<script setup lang="ts">
/**
 * État vide illustré : un dessin au trait aux couleurs de l'accent, un titre, une phrase d'aide et, en option, une action
 * (slot). L'illustration est décorative (masquée aux lecteurs d'écran) : le texte porte l'information.
 */
export type EmptyIcon = 'guests' | 'messages' | 'photos' | 'gift' | 'calendar' | 'event' | 'table';

withDefaults(defineProps<{ icon: EmptyIcon; title: string; text?: string; compact?: boolean }>(), { compact: false });
</script>

<template>
    <div class="flex flex-col items-center text-center" :class="compact ? 'gap-1 py-4' : 'gap-2 px-4 py-10'" data-testid="empty-state">
        <svg
            class="text-accent"
            :class="compact ? 'h-12 w-12' : 'h-20 w-20'"
            viewBox="0 0 64 64"
            fill="none"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
            focusable="false"
        >
            <circle cx="32" cy="32" r="29" fill="var(--surface-300)" stroke="none" />

            <template v-if="icon === 'guests'">
                <circle cx="25" cy="25" r="6" />
                <path d="M13 46c0-7 5-12 12-12s12 5 12 12" />
                <circle cx="43" cy="27" r="5" />
                <path d="M40 35c6-1 11 3 11 10" />
            </template>

            <template v-else-if="icon === 'messages'">
                <path d="M16 18h32a3 3 0 0 1 3 3v17a3 3 0 0 1-3 3H33l-9 8v-8h-8a3 3 0 0 1-3-3V21a3 3 0 0 1 3-3Z" />
                <path d="M23 27h18M23 33h11" />
            </template>

            <template v-else-if="icon === 'photos'">
                <rect x="12" y="17" width="40" height="30" rx="2" />
                <circle cx="24" cy="27" r="3" />
                <path d="m14 44 12-11 8 7 7-6 9 9" />
            </template>

            <template v-else-if="icon === 'gift'">
                <rect x="14" y="26" width="36" height="22" rx="2" />
                <path d="M12 20h40v6H12zM32 20v28" />
                <path d="M32 20c-4-8-12-8-12-3s8 3 12 3Zm0 0c4-8 12-8 12-3s-8 3-12 3Z" />
            </template>

            <template v-else-if="icon === 'calendar'">
                <rect x="14" y="18" width="36" height="32" rx="2" />
                <path d="M14 28h36M24 14v8M40 14v8" />
                <path d="M23 36h4M31 36h4M39 36h4M23 43h4M31 43h4" />
            </template>

            <template v-else-if="icon === 'table'">
                <circle cx="32" cy="32" r="10" />
                <circle cx="32" cy="14" r="3.5" />
                <circle cx="32" cy="50" r="3.5" />
                <circle cx="14" cy="32" r="3.5" />
                <circle cx="50" cy="32" r="3.5" />
            </template>

            <template v-else>
                <path d="M32 12c1 11 9 19 20 20-11 1-19 9-20 20-1-11-9-19-20-20 11-1 19-9 20-20Z" />
                <path d="M48 12v6M45 15h6" />
            </template>
        </svg>

        <p :class="compact ? 'label' : 'display text-2xl'">{{ title }}</p>
        <p v-if="text" class="max-w-sm text-sm text-ink-muted">{{ text }}</p>
        <div v-if="$slots.default" class="mt-3"><slot /></div>
    </div>
</template>
