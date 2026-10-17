<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const locales = [
    { code: 'fr', label: 'FR' },
    { code: 'en', label: 'EN' },
];

const current = computed(() => usePage().props.locale);

const change = (code: string) => {
    if (code !== current.value) {
        router.post(route('locale.update'), { locale: code }, { preserveScroll: true });
    }
};
</script>

<template>
    <div class="inline-flex rounded-full border border-current/20 p-0.5 text-xs font-semibold" role="group" :aria-label="__('Langue')">
        <button
            v-for="l in locales"
            :key="l.code"
            type="button"
            class="rounded-full px-2.5 py-1 transition"
            :class="current === l.code ? 'bg-[var(--ink)] text-[var(--paper)]' : 'opacity-60 hover:opacity-100'"
            :aria-pressed="current === l.code"
            @click="change(l.code)"
        >
            {{ l.label }}
        </button>
    </div>
</template>
