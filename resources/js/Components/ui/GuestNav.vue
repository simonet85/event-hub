<script setup lang="ts">
import { computed } from 'vue';

/** Barre de navigation mobile fixée en bas : ancres vers les sections de la page d'invitation. */
const props = defineProps<{
    hasSchedule?: boolean;
    hasGifts?: boolean;
    withRsvp?: boolean;
}>();

const items = computed(() =>
    [
        { href: '#invitation', label: 'Invitation', show: true },
        { href: '#rsvp', label: 'RSVP', show: props.withRsvp },
        { href: '#programme', label: 'Programme', show: props.hasSchedule },
        { href: '#livre-dor', label: "Livre d'or", show: props.withRsvp },
        { href: '#photos', label: 'Photos', show: props.withRsvp },
        { href: '#cadeaux', label: 'Cadeaux', show: props.hasGifts },
    ].filter((i) => i.show),
);
</script>

<template>
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface-100" aria-label="Navigation de l'invitation" data-testid="guest-nav">
        <ul class="mx-auto flex max-w-3xl overflow-x-auto">
            <li v-for="i in items" :key="i.href" class="min-w-0 flex-1">
                <a :href="i.href" class="flex min-h-tap items-center justify-center px-2 text-center text-xs font-semibold text-ink hover:bg-surface-200">
                    {{ i.label }}
                </a>
            </li>
        </ul>
    </nav>
</template>
