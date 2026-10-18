<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    tone?: 'default' | 'good' | 'bad' | 'warn';
}>();

const tones = { default: '', good: 'text-success', bad: 'text-danger', warn: 'text-warning' };
</script>

<template>
    <div class="card px-4 py-4">
        <dt class="eyebrow !text-[0.65rem]">{{ label }}</dt>
        <dd class="display mt-1 text-4xl" :class="tones[tone ?? 'default']">{{ value }}</dd>
    </div>
</template>
