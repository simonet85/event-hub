<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    theme: { primary_color: string; secondary_color: string; font_family: string };
}>();

const fonts: Record<string, string> = {
    script: '"Great Vibes", cursive',
    serif: '"Playfair Display", serif',
    body: 'Montserrat, sans-serif',
};

// Les couleurs du thème de l'événement remplacent les jetons du design system dans toute la page.
const style = computed(() => ({
    '--event-primary': props.theme.primary_color,
    '--event-secondary': props.theme.secondary_color,
    '--event-font': fonts[props.theme.font_family] ?? fonts.body,
    '--accent': props.theme.primary_color,
    '--ink': props.theme.primary_color,
    '--paper': props.theme.secondary_color,
    '--muted': `color-mix(in srgb, ${props.theme.primary_color} 70%, ${props.theme.secondary_color})`,
    '--line': `color-mix(in srgb, ${props.theme.primary_color} 18%, transparent)`,
    backgroundColor: props.theme.secondary_color,
    color: props.theme.primary_color,
}));
</script>

<template>
    <div class="relative min-h-screen overflow-x-hidden" :style="style">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-[60vh] opacity-60"
            style="background: radial-gradient(60% 80% at 50% 0%, color-mix(in srgb, var(--event-primary) 16%, transparent), transparent)"
            aria-hidden="true"
        />
        <main class="relative mx-auto flex min-h-screen max-w-3xl flex-col items-center px-4 pb-20 text-center">
            <slot />
        </main>
    </div>
</template>
