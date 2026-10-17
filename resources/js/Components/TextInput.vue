<script setup lang="ts">
import { computed, onMounted, ref, useAttrs } from 'vue';

defineOptions({ inheritAttrs: false });

const model = defineModel<string>({ required: true });
const attrs = useAttrs();

const input = ref<HTMLInputElement | null>(null);
const revealed = ref(false);

const isPassword = computed(() => attrs.type === 'password');
const type = computed(() => (isPassword.value && revealed.value ? 'text' : (attrs.type as string | undefined)));
const inputAttrs = computed(() => {
    const { class: _class, type: _type, ...rest } = attrs;
    return rest;
});

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div class="relative" :class="attrs.class">
        <input v-bind="inputAttrs" ref="input" v-model="model" :type="type" class="input" :class="isPassword ? 'pe-12' : ''" />
        <button
            v-if="isPassword"
            type="button"
            class="absolute inset-y-0 end-0 flex w-12 items-center justify-center text-[var(--muted)] hover:text-[var(--ink)]"
            :aria-label="revealed ? __('Masquer le mot de passe') : __('Afficher le mot de passe')"
            :aria-pressed="revealed"
            tabindex="-1"
            @click="revealed = !revealed"
        >
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" />
                <circle cx="12" cy="12" r="3" />
                <path v-if="revealed" stroke-linecap="round" d="M4 4l16 16" />
            </svg>
        </button>
    </div>
</template>
