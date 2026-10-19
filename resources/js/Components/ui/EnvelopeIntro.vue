<script setup lang="ts">
import { markEnvelopePlayed, shouldPlayEnvelope } from '@/lib/envelope';
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{ token: string; guestName: string }>();

/** Durée de l'animation (ouverture, lettre, fondu) ; le repli garantit le retrait même sans événement d'animation. */
const TOTAL_MS = 2600;
const SKIP_EVENTS = ['pointerdown', 'keydown', 'wheel', 'touchmove'] as const;

const playing = ref(false);
const leaving = ref(false);
let fallback: ReturnType<typeof setTimeout> | undefined;

function stop() {
    playing.value = false;
    clearTimeout(fallback);
    SKIP_EVENTS.forEach((name) => window.removeEventListener(name, skip));
}

// Le moindre geste (toucher, clic, touche, molette) referme l'enveloppe ; rien n'est bloqué : le geste atteint aussi la page.
function skip() {
    if (leaving.value) return;
    leaving.value = true;
    clearTimeout(fallback);
    fallback = setTimeout(stop, 220);
}

onMounted(() => {
    if (!shouldPlayEnvelope(props.token)) return;

    markEnvelopePlayed(props.token);
    playing.value = true;
    SKIP_EVENTS.forEach((name) => window.addEventListener(name, skip, { passive: true }));
    fallback = setTimeout(stop, TOTAL_MS + 200);
});
onBeforeUnmount(stop);
</script>

<template>
    <div v-if="playing" class="envelope-intro" :class="{ 'is-leaving': leaving }" aria-hidden="true" data-testid="envelope-intro" @animationend.self="stop">
        <div class="envelope">
            <div class="envelope-letter">
                <p class="eyebrow">Une invitation pour</p>
                <p class="envelope-name">{{ guestName }}</p>
            </div>
            <div class="envelope-front" />
            <div class="envelope-flap" />
            <span class="envelope-seal">✦</span>
        </div>
    </div>
</template>
