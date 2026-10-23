<script setup lang="ts">
import { translate as __ } from '@/i18n';
import EmptyState from '@/Components/ui/EmptyState.vue';
import EventHeader from '@/Components/ui/EventHeader.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    event: { title: string; slug: string };
    photos: { id: number; thumb: string; url: string }[];
    limits: { per_event: number; per_upload: number; size_mb: number };
}>();

const form = useForm<{ photos: File[] }>({ photos: [] });

// Change la clé du champ fichier natif après un envoi : le navigateur n'efface pas autrement les noms affichés.
const fileInputKey = ref(0);
const confirmingId = ref<number | null>(null);

const added = computed(() => (usePage().props.flash as { gallery_added?: number } | undefined)?.gallery_added);
const photosError = computed(() => Object.entries(form.errors).find(([key]) => key.startsWith('photos'))?.[1]);

const pick = (e: Event) => {
    form.photos = Array.from((e.target as HTMLInputElement).files ?? []);
};

const upload = () =>
    form.post(route('gallery.store', props.event.slug), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            fileInputKey.value++;
        },
    });

const remove = (id: number) => {
    confirmingId.value = null;
    router.delete(route('gallery.destroy', [props.event.slug, id]), { preserveScroll: true });
};
</script>

<template>
    <Head :title="`${__('Galerie')} - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <EventHeader :event="event" :title="__('Galerie')" current="gallery" />
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <section class="card space-y-4 p-6" data-testid="gallery-upload">
                    <div>
                        <h3 class="display text-2xl">{{ __('Ajouter des photos') }}</h3>
                        <p class="mt-1 max-w-2xl text-sm text-ink-muted">
                            {{ __('Les invités les voient dans l\'onglet Galerie de leur invitation et peuvent les télécharger, une par une ou toutes ensemble. Les photos sont réduites et leurs métadonnées (position GPS comprise) sont retirées.') }}
                        </p>
                    </div>

                    <form class="space-y-3" @submit.prevent="upload">
                        <input
                            :key="fileInputKey"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                            :aria-label="__('Photos à ajouter')"
                            data-testid="gallery-input"
                            @change="pick"
                        />
                        <p class="caption text-ink-muted">
                            {{ __('JPEG, PNG ou WebP, :size Mo maximum par photo, :count photos par envoi, :max photos au total.', { size: limits.size_mb, count: limits.per_upload, max: limits.per_event }) }}
                        </p>
                        <p v-if="photosError" class="text-sm text-danger" role="alert" data-testid="gallery-error">{{ photosError }}</p>
                        <p v-if="added" class="notice notice-success" role="status" data-testid="gallery-added">{{ __(':count photo(s) ajoutée(s).', { count: added }) }}</p>

                        <div v-if="form.processing && form.progress" class="h-2 overflow-hidden rounded-full bg-surface-300" role="progressbar" :aria-valuenow="form.progress.percentage" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full bg-accent transition-all" :style="{ width: `${form.progress.percentage}%` }" />
                        </div>

                        <button type="submit" class="btn" :disabled="form.processing || form.photos.length === 0">
                            {{ form.processing ? __('Envoi en cours…') : __('Envoyer :count photo(s)', { count: form.photos.length }) }}
                        </button>
                    </form>
                </section>

                <section>
                    <h3 class="mb-3 display text-2xl">{{ __('Photos de l\'événement') }} <span class="text-base text-ink-muted">({{ photos.length }})</span></h3>
                    <EmptyState v-if="photos.length === 0" class="card" icon="photos" :title="__('Aucune photo')" :text="__('Ajoutez les photos de la fête : vos invités pourront les voir et les télécharger.')" />
                    <ul v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5" data-testid="gallery-grid">
                        <li v-for="p in photos" :key="p.id" class="overflow-hidden card">
                            <a :href="p.url" target="_blank" rel="noopener"><img :src="p.thumb" :alt="__('Photo de l\'événement')" loading="lazy" class="aspect-square w-full object-cover" /></a>
                            <div class="p-2">
                                <button v-if="confirmingId !== p.id" type="button" class="chip cursor-pointer !text-danger hover:!bg-surface-300" @click="confirmingId = p.id">{{ __('Supprimer') }}</button>
                                <div v-else class="flex gap-2">
                                    <button type="button" class="chip cursor-pointer !text-danger hover:!bg-surface-300" data-testid="gallery-confirm-delete" @click="remove(p.id)">{{ __('Confirmer') }}</button>
                                    <button type="button" class="chip cursor-pointer hover:!bg-surface-300" @click="confirmingId = null">{{ __('Annuler') }}</button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
