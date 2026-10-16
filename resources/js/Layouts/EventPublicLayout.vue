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

const style = computed(() => ({
    '--event-primary': props.theme.primary_color,
    '--event-secondary': props.theme.secondary_color,
    '--event-font': fonts[props.theme.font_family] ?? fonts.body,
}));
</script>

<template>
    <div
        class="min-h-screen overflow-x-hidden"
        :style="{ ...style, backgroundColor: 'var(--event-secondary)', color: 'var(--event-primary)' }"
    >
        <main class="mx-auto flex min-h-screen max-w-3xl flex-col items-center justify-center px-4 py-10 text-center">
            <slot />
        </main>
    </div>
</template>
