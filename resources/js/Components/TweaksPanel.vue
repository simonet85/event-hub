<script setup lang="ts">
import { EVENT_FONTS } from '@/lib/fonts';
import type { Theme } from '@/types';
import { computed } from 'vue';

const props = defineProps<{
    modelValue: Theme;
    /** Préréglages par type d'événement ; ceux qui ont le même thème (Mariage, Autre) ne sont proposés qu'une fois. */
    presets: { value: string; label: string; theme: Theme }[];
    /** Messages d'avertissement de contraste (point 4), affichés sous le panneau. */
    problems?: string[];
}>();

const emit = defineEmits<{ 'update:modelValue': [Theme] }>();

const same = (a: Theme, b: Theme) =>
    a.primary_color.toLowerCase() === b.primary_color.toLowerCase() &&
    a.secondary_color.toLowerCase() === b.secondary_color.toLowerCase() &&
    a.font_family === b.font_family;

const uniquePresets = computed(() => props.presets.filter((p, i) => props.presets.findIndex((q) => same(q.theme, p.theme)) === i));

const change = (patch: Partial<Theme>) => emit('update:modelValue', { ...props.modelValue, ...patch });
</script>

<template>
    <fieldset class="space-y-5 border border-line bg-surface-100 p-4" data-testid="tweaks">
        <legend class="eyebrow px-2">{{ __('Apparence') }}</legend>

        <div>
            <p id="tweaks-presets" class="field-label">{{ __('Préréglage') }}</p>
            <div class="flex flex-wrap gap-2" role="group" aria-labelledby="tweaks-presets">
                <button
                    v-for="p in uniquePresets"
                    :key="p.value"
                    type="button"
                    class="chip"
                    :class="same(modelValue, p.theme) ? '!border-accent' : ''"
                    :aria-pressed="same(modelValue, p.theme)"
                    :data-testid="`preset-${p.value}`"
                    @click="emit('update:modelValue', { ...p.theme })"
                >
                    <span class="inline-block h-3 w-3 rounded-full border border-line-strong" :style="{ background: p.theme.primary_color }" aria-hidden="true" />
                    {{ p.label }}
                </button>
            </div>
        </div>

        <div class="flex flex-wrap items-end gap-6">
            <div>
                <label for="primary_color" class="field-label">{{ __('Couleur d\'accent') }}</label>
                <input id="primary_color" :value="modelValue.primary_color" type="color" class="color-input" @input="change({ primary_color: ($event.target as HTMLInputElement).value })" />
            </div>
            <div>
                <label for="secondary_color" class="field-label">{{ __('Fond') }}</label>
                <input id="secondary_color" :value="modelValue.secondary_color" type="color" class="color-input" @input="change({ secondary_color: ($event.target as HTMLInputElement).value })" />
            </div>
        </div>

        <div>
            <p id="tweaks-fonts" class="field-label">{{ __('Police') }}</p>
            <div class="flex flex-wrap gap-2" role="group" aria-labelledby="tweaks-fonts">
                <button
                    v-for="(font, key) in EVENT_FONTS"
                    :key="key"
                    type="button"
                    class="chip"
                    :class="modelValue.font_family === key ? '!border-accent' : ''"
                    :aria-pressed="modelValue.font_family === key"
                    :data-testid="`font-${key}`"
                    @click="change({ font_family: key })"
                >
                    <span class="text-lg leading-none" :style="{ fontFamily: font.stack }" aria-hidden="true">{{ __('Aa') }}</span>
                    {{ __(font.label) }}
                </button>
            </div>
        </div>

        <p v-for="(problem, i) in problems ?? []" :key="i" class="notice notice-warning" role="alert" data-testid="contrast-warning">{{ problem }}</p>
    </fieldset>
</template>
