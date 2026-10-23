<script setup lang="ts">
import EmptyState from '@/Components/ui/EmptyState.vue';
import PhotoViewer from '@/Components/ui/PhotoViewer.vue';
import { computed, ref } from 'vue';

export interface GalleryPhoto {
    id: number;
    thumb: string;
    url: string;
    download: string;
}

const props = defineProps<{ token: string; photos: GalleryPhoto[] }>();

/** Même plafond que le serveur : au-delà, une sélection serait tronquée. */
const MAX_SELECTION = 100;

const viewerIndex = ref<number | null>(null);
const selecting = ref(false);
const selected = ref<number[]>([]);

const archiveUrl = computed(() => String(route('guest-gallery.archive', props.token)));
const selectionUrl = computed(() => `${archiveUrl.value}?${selected.value.map((id) => `ids[]=${id}`).join('&')}`);
const viewerPhotos = computed(() => props.photos.map((p) => ({ id: p.id, url: p.url, download: p.download })));

function toggleSelecting() {
    selecting.value = !selecting.value;
    selected.value = [];
}

function toggle(id: number) {
    if (selected.value.includes(id)) selected.value = selected.value.filter((i) => i !== id);
    else if (selected.value.length < MAX_SELECTION) selected.value = [...selected.value, id];
}

function open(index: number, id: number) {
    if (selecting.value) toggle(id);
    else viewerIndex.value = index;
}
</script>

<template>
    <section id="galerie" class="card w-full max-w-md scroll-mt-4 space-y-4 p-6 text-left" data-testid="gallery">
        <div class="text-center">
            <p class="eyebrow">{{ __('Galerie photo') }}</p>
            <h2 class="display-lg mt-1">{{ __('Les') }} <em>{{ __('photos') }}</em></h2>
        </div>

        <EmptyState v-if="photos.length === 0" compact icon="photos" :title="__('Aucune photo pour le moment')" :text="__('Les photos de la fête seront publiées ici par les hôtes.')" />

        <template v-else>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="caption text-ink-muted" data-testid="gallery-count">{{ __(':count photo(s)', { count: photos.length }) }}</p>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="btn btn-secondary !px-4 !py-2 text-xs" :aria-pressed="selecting" data-testid="gallery-select" @click="toggleSelecting">
                        {{ selecting ? __('Annuler') : __('Sélectionner') }}
                    </button>
                    <a :href="archiveUrl" class="btn !px-4 !py-2 text-xs" data-testid="gallery-download-all">{{ __('Tout télécharger') }}</a>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-1.5">
                <button
                    v-for="(p, i) in photos"
                    :key="p.id"
                    type="button"
                    class="relative block"
                    :aria-label="selecting ? __('Sélectionner la photo :n', { n: i + 1 }) : __('Agrandir la photo :n', { n: i + 1 })"
                    :aria-pressed="selecting ? selected.includes(p.id) : undefined"
                    data-testid="photo-open"
                    @click="open(i, p.id)"
                >
                    <img :src="p.thumb" :alt="__('Photo :n', { n: i + 1 })" loading="lazy" class="aspect-square w-full rounded-md object-cover" :class="selected.includes(p.id) ? 'opacity-60 ring-2 ring-accent' : ''" />
                    <span v-if="selecting" class="absolute right-1.5 top-1.5 flex size-5 items-center justify-center rounded-full border-2 border-white bg-black/40 text-xs text-white" aria-hidden="true">{{ selected.includes(p.id) ? '✓' : '' }}</span>
                </button>
            </div>

            <div v-if="selecting" class="space-y-1 text-center">
                <a
                    v-if="selected.length"
                    :href="selectionUrl"
                    class="btn w-full"
                    data-testid="gallery-download-selection"
                    @click="toggleSelecting"
                >
                    {{ __('Télécharger la sélection (:count)', { count: selected.length }) }}
                </a>
                <p v-else class="caption text-ink-muted">{{ __('Touchez les photos à télécharger.') }}</p>
                <p v-if="selected.length >= MAX_SELECTION" class="caption text-ink-muted">{{ __('Sélection limitée à :max photos.', { max: MAX_SELECTION }) }}</p>
            </div>

            <PhotoViewer v-model:index="viewerIndex" :photos="viewerPhotos" />
        </template>
    </section>
</template>
