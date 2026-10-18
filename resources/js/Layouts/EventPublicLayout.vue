<script setup lang="ts">
import { onAccent } from '@/lib/contrast';
import { computed } from 'vue';

const props = defineProps<{
    theme: { primary_color: string; secondary_color: string; font_family: string };
    /** Préréglage du type d'événement : une valeur identique au préréglage n'est pas « personnalisée ». */
    preset?: { primary_color: string; secondary_color: string; font_family: string };
    /** Classe ev-* du type (mariage, bapteme, anniversaire, fete) : fixe --accent. */
    cssClass?: string;
    /** Thème Fête : mode sombre, sauf si l'hôte a choisi son propre fond. */
    dark?: boolean;
}>();

const fonts: Record<string, string> = {
    script: '"Pinyon Script", "Snell Roundhand", cursive',
    serif: '"Cormorant Garamond", Cormorant, Georgia, serif',
    body: '"DM Sans", system-ui, sans-serif',
};

const differs = (key: 'primary_color' | 'secondary_color' | 'font_family') =>
    !props.preset || props.theme[key].toLowerCase() !== props.preset[key].toLowerCase();

const customAccent = computed(() => differs('primary_color'));
const customSurface = computed(() => differs('secondary_color'));
const darkMode = computed(() => !!props.dark && !customSurface.value);

// Les jetons du Design System (clair/sombre + ev-*) font le gros du travail ; les couleurs choisies
// par l'hôte ne remplacent le préréglage que si elles en diffèrent.
const style = computed(() => {
    const vars: Record<string, string> = {
        '--event-primary': props.theme.primary_color,
        '--event-secondary': props.theme.secondary_color,
        '--event-font': fonts[props.theme.font_family] ?? fonts.serif,
    };

    if (customAccent.value) {
        vars['--accent'] = props.theme.primary_color;
        vars['--on-accent'] = onAccent(props.theme.primary_color);
    }
    if (customSurface.value) {
        const bg = props.theme.secondary_color;
        vars['--surface-100'] = bg;
        vars['--surface-200'] = `color-mix(in srgb, ${bg} 94%, var(--ink))`;
        vars['--surface-300'] = `color-mix(in srgb, ${bg} 88%, var(--ink))`;
    }

    return vars;
});
</script>

<template>
    <div
        class="relative min-h-screen overflow-x-hidden bg-surface-100 text-ink"
        :class="cssClass"
        :data-theme="darkMode ? 'dark' : 'light'"
        :style="style"
    >
        <main class="relative mx-auto flex min-h-screen max-w-3xl flex-col items-center px-4 pb-28 text-center">
            <slot />
        </main>
    </div>
</template>
