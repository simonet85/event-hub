<script setup lang="ts">
import { neighbour, swipeDirection } from '@/lib/swipe';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

export interface ViewerPhoto {
    id: number;
    url: string;
    author: string;
    caption: string | null;
}

/**
 * Visionneuse plein écran. Fenêtre modale native (`<dialog>`) : le navigateur piège le focus, rend le reste de
 * la page inerte et rend le focus au miniature qui l'a ouverte. Clavier : ← →, Début, Fin, Échap.
 * Tactile : glisser à gauche ou à droite pour changer de photo, vers le bas pour fermer.
 */
const props = defineProps<{ photos: ViewerPhoto[]; index: number | null }>();
const emit = defineEmits<{ 'update:index': [value: number | null] }>();

const dialog = ref<HTMLDialogElement | null>(null);
const isOpen = computed(() => props.index !== null && props.photos.length > 0);
const photo = computed(() => (isOpen.value ? props.photos[props.index as number] : null));
const atStart = computed(() => props.index === 0);
const atEnd = computed(() => props.index === props.photos.length - 1);

const close = () => emit('update:index', null);
const go = (step: 1 | -1) => {
    if (props.index !== null) {
        emit('update:index', neighbour(props.index, step, props.photos.length));
    }
};

watch(
    isOpen,
    async (open) => {
        await nextTick();
        if (open) {
            if (!dialog.value?.open) dialog.value?.showModal?.();
        } else if (dialog.value?.open) {
            dialog.value.close?.();
        }
    },
    { immediate: true },
);

// Précharge les voisines pour que le changement de photo soit immédiat, même sur un réseau lent.
watch(
    () => props.index,
    (i) => {
        if (i === null) return;
        for (const n of [i - 1, i + 1]) {
            if (props.photos[n]) new Image().src = props.photos[n].url;
        }
    },
);

const onKeydown = (e: KeyboardEvent) => {
    const actions: Record<string, () => void> = {
        ArrowLeft: () => go(-1),
        ArrowRight: () => go(1),
        Home: () => emit('update:index', 0),
        End: () => emit('update:index', props.photos.length - 1),
    };

    if (actions[e.key]) {
        e.preventDefault();
        actions[e.key]();
    }
};

let start: { x: number; y: number } | null = null;
const onPointerDown = (e: PointerEvent) => {
    if (e.pointerType !== 'mouse') start = { x: e.clientX, y: e.clientY };
};
const onPointerUp = (e: PointerEvent) => {
    if (!start) return;
    const direction = swipeDirection(start.x, start.y, e.clientX, e.clientY);
    start = null;

    if (direction === 'left') go(1);
    else if (direction === 'right') go(-1);
    else if (direction === 'down') close();
};

const onBackdrop = (e: MouseEvent) => {
    if (e.target === e.currentTarget) close();
};

onBeforeUnmount(() => {
    if (dialog.value?.open) dialog.value.close?.();
});
</script>

<template>
    <dialog
        ref="dialog"
        data-theme="dark"
        class="m-0 h-full max-h-none w-full max-w-none bg-transparent p-0 text-ink backdrop:bg-transparent"
        aria-label="Visionneuse de photos"
        data-testid="photo-viewer"
        @cancel.prevent="close"
        @keydown="onKeydown"
    >
        <div
            v-if="photo"
            class="flex h-full w-full flex-col items-center justify-center gap-4 p-4"
            style="background: color-mix(in srgb, var(--surface-100) 96%, transparent)"
            @click="onBackdrop"
        >
            <div class="flex w-full max-w-5xl items-center justify-between">
                <p class="label" aria-live="polite" data-testid="viewer-counter">{{ (index as number) + 1 }} / {{ photos.length }}</p>
                <button type="button" class="btn btn-secondary" data-testid="viewer-close" autofocus @click="close">Fermer</button>
            </div>

            <div class="flex w-full max-w-5xl flex-1 items-center justify-center gap-2 overflow-hidden" style="touch-action: pan-y" @pointerdown="onPointerDown" @pointerup="onPointerUp">
                <button type="button" class="btn btn-secondary shrink-0 !px-3" aria-label="Photo précédente" data-testid="viewer-prev" :disabled="atStart" @click="go(-1)">‹</button>
                <img :key="photo.id" :src="photo.url" :alt="photo.caption ?? `Photo de ${photo.author}`" class="max-h-[70vh] min-w-0 max-w-full select-none object-contain" draggable="false" data-testid="viewer-image" />
                <button type="button" class="btn btn-secondary shrink-0 !px-3" aria-label="Photo suivante" data-testid="viewer-next" :disabled="atEnd" @click="go(1)">›</button>
            </div>

            <p class="caption max-w-xl text-center text-ink-muted" data-testid="viewer-caption">
                {{ photo.caption ? `${photo.caption} — ` : '' }}{{ photo.author }}
            </p>
        </div>
    </dialog>
</template>
