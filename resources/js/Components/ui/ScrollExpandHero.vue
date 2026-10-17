<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

/**
 * Hero « scroll expansion » : le visuel est coupé en deux moitiés qui s'écartent au scroll
 * et révèlent le contenu du slot par défaut. `progress` (0 → 1) est exposé au slot.
 */
const root = ref<HTMLElement | null>(null);
const progress = ref(0);
const reduced = ref(false);

const update = () => {
    if (!root.value) {
        return;
    }
    const max = root.value.offsetHeight - window.innerHeight;
    const raw = max > 0 ? -root.value.getBoundingClientRect().top / max : 0;
    progress.value = Math.min(Math.max(raw, 0), 1);
};

onMounted(() => {
    reduced.value = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
    if (reduced.value) {
        progress.value = 1;
        return;
    }
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    update();
});
onBeforeUnmount(() => {
    window.removeEventListener('scroll', update);
    window.removeEventListener('resize', update);
});

defineExpose({ progress });
</script>

<template>
    <div ref="root" :class="reduced ? '' : 'h-[240vh]'" data-testid="scroll-hero">
        <div :class="reduced ? '' : 'sticky top-0 h-screen overflow-hidden'">
            <div class="absolute inset-0 flex items-center justify-center" :style="{ opacity: 0.35 + progress * 0.65 }">
                <slot name="reveal" :progress="progress" />
            </div>
            <template v-if="!reduced">
                <div
                    class="absolute inset-y-0 left-0 w-1/2 overflow-hidden will-change-transform"
                    :style="{ transform: `translateX(${-progress * 100}%)` }"
                    data-testid="hero-left"
                >
                    <div class="absolute inset-y-0 left-0 w-[200%]"><slot name="cover" /></div>
                </div>
                <div
                    class="absolute inset-y-0 right-0 w-1/2 overflow-hidden will-change-transform"
                    :style="{ transform: `translateX(${progress * 100}%)` }"
                    data-testid="hero-right"
                >
                    <div class="absolute inset-y-0 right-0 w-[200%]"><slot name="cover" /></div>
                </div>
            </template>
        </div>
    </div>
</template>
