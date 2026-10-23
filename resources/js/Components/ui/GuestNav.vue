<script setup lang="ts">
import { tabUrl, type GuestTab } from '@/lib/guestTabs';

/** Barre d'onglets fixée en bas de la page d'invitation ; l'onglet actif est repris dans l'URL. */
defineProps<{
    tabs: { key: GuestTab; label: string }[];
    active: GuestTab;
}>();

defineEmits<{ select: [tab: GuestTab] }>();
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface-100 print:hidden" :aria-label="__('Navigation de l\'invitation')" data-testid="guest-nav">
        <ul class="mx-auto flex max-w-3xl">
            <li v-for="t in tabs" :key="t.key" class="min-w-0 flex-1">
                <a
                    :href="tabUrl(t.key)"
                    :aria-current="t.key === active ? 'page' : undefined"
                    class="flex min-h-tap items-center justify-center px-1 text-center text-xs font-semibold hover:bg-surface-200"
                    :class="t.key === active ? 'text-accent underline decoration-2 underline-offset-8' : 'text-ink'"
                    @click.prevent="$emit('select', t.key)"
                >
                    {{ t.label }}
                </a>
            </li>
        </ul>
    </nav>
</template>
