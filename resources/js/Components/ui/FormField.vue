<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref } from 'vue';

defineOptions({ inheritAttrs: false });

defineProps<{
    label: string;
    id: string;
    error?: string;
    hint?: string;
}>();

const model = defineModel<string>({ required: true });
const input = ref<InstanceType<typeof TextInput> | null>(null);

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <div>
        <label :for="id" class="field-label">{{ label }}</label>
        <TextInput :id="id" ref="input" v-model="model" v-bind="$attrs" class="w-full" :aria-invalid="error ? 'true' : undefined" />
        <p v-if="hint && !error" class="mt-1.5 text-xs text-[var(--muted)]">{{ hint }}</p>
        <InputError class="mt-1.5" :message="error" />
    </div>
</template>
