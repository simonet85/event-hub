<script setup lang="ts">
defineProps<{
    message?: string;
}>();
</script>

<template>
    <div v-show="message" role="alert">
        <p class="flex items-start gap-1.5 text-sm text-[#b42318]">
            <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm-.75-11.5a.75.75 0 0 1 1.5 0v4a.75.75 0 0 1-1.5 0v-4Zm.75 7a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z" clip-rule="evenodd" />
            </svg>
            {{ message }}
        </p>
    </div>
</template>
