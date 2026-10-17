<script setup lang="ts">
defineProps<{ items: string[] }>();
</script>

<template>
    <div class="marquee" data-testid="marquee">
        <!-- Deux pistes identiques : la seconde, masquée aux lecteurs d'écran, assure la boucle sans saut. -->
        <div v-for="copy in 2" :key="copy" class="marquee-track" :aria-hidden="copy === 2 ? 'true' : undefined">
            <span v-for="(item, i) in items" :key="i" class="flex items-center gap-10 whitespace-nowrap">
                {{ item }}
                <span aria-hidden="true">✦</span>
            </span>
        </div>
    </div>
</template>
