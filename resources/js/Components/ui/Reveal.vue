<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = withDefaults(defineProps<{ delay?: number }>(), { delay: 0 });

const el = ref<HTMLElement | null>(null);
// Sans IntersectionObserver (anciens navigateurs, tests), le contenu reste visible.
const visible = ref(typeof IntersectionObserver === 'undefined');
let observer: IntersectionObserver | undefined;

onMounted(() => {
    if (visible.value || !el.value) {
        return;
    }
    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry.isIntersecting) {
                visible.value = true;
                observer?.disconnect();
            }
        },
        { threshold: 0.12 },
    );
    observer.observe(el.value);
});
onBeforeUnmount(() => observer?.disconnect());
</script>

<template>
    <div ref="el" class="reveal" :class="{ 'is-visible': visible }" :style="{ transitionDelay: `${props.delay}ms` }">
        <slot />
    </div>
</template>
