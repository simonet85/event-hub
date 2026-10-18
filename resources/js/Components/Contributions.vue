<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    token: string;
    guestbook: { id: number; author: string; message: string }[];
    photos: { id: number; author: string; caption: string | null; url: string }[];
    mine: { pending_entries: number; pending_photos: number };
}>();

const entryForm = useForm({ message: '' });
const photoForm = useForm<{ photo: File | null; caption: string }>({ photo: null, caption: '' });

// Change la clé du champ fichier natif après un envoi : le navigateur n'efface pas son nom autrement.
const fileInputKey = ref(0);

const saved = computed(() => (usePage().props.flash as { contribution_saved?: string } | undefined)?.contribution_saved);

const sendEntry = () => entryForm.post(route('guestbook.store', props.token), { preserveScroll: true, onSuccess: () => entryForm.reset() });
const sendPhoto = () =>
    photoForm.post(route('photos.store', props.token), {
        preserveScroll: true,
        onSuccess: () => {
            photoForm.reset();
            fileInputKey.value++;
        },
    });
</script>

<template>
    <div class="w-full max-w-md space-y-6 text-left">
        <section id="livre-dor" class="card scroll-mt-4 space-y-4 p-6" data-testid="guestbook">
            <div class="text-center"><p class="eyebrow">Livre d'or</p><h2 class="display-lg mt-1">Vos <em>mots</em></h2></div>

            <ul v-if="guestbook.length" class="space-y-3">
                <li v-for="e in guestbook" :key="e.id" class="border-b border-line pb-3 last:border-0">
                    <p class="whitespace-pre-line">{{ e.message }}</p>
                    <p class="caption mt-1 text-ink-muted">— {{ e.author }}</p>
                </li>
            </ul>
            <p v-else class="caption text-center text-ink-muted">Soyez le premier à laisser un mot.</p>

            <form class="space-y-2" @submit.prevent="sendEntry">
                <textarea
                    v-model="entryForm.message"
                    rows="3"
                    placeholder="Laissez un message aux hôtes…"
                    class="input"
                />
                <p v-if="entryForm.errors.message" class="text-sm text-danger">{{ entryForm.errors.message }}</p>
                <button
                    type="submit"
                    :disabled="entryForm.processing || !entryForm.message"
                    class="w-full btn"
                >
                    Publier mon message
                </button>
            </form>
            <p v-if="saved === 'entry' || mine.pending_entries > 0" class="notice notice-warning text-center" data-testid="entry-pending">
                Votre message sera visible après validation par les hôtes.
            </p>
        </section>

        <section id="photos" class="card scroll-mt-4 space-y-4 p-6" data-testid="gallery">
            <div class="text-center"><p class="eyebrow">Galerie photo</p><h2 class="display-lg mt-1">Vos <em>photos</em></h2></div>

            <div v-if="photos.length" class="grid grid-cols-2 gap-2">
                <figure v-for="p in photos" :key="p.id">
                    <img :src="p.url" :alt="p.caption ?? `Photo de ${p.author}`" loading="lazy" class="aspect-square w-full rounded-lg object-cover" />
                    <figcaption class="caption mt-1 text-ink-muted">{{ p.caption ? `${p.caption} — ` : '' }}{{ p.author }}</figcaption>
                </figure>
            </div>
            <p v-else class="caption text-center text-ink-muted">Aucune photo pour le moment.</p>

            <form class="space-y-2" @submit.prevent="sendPhoto">
                <input
                    :key="fileInputKey"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    @change="photoForm.photo = ($event.target as HTMLInputElement).files?.[0] ?? null"
                />
                <input v-model="photoForm.caption" type="text" placeholder="Légende (optionnel)" class="input" />
                <p v-if="photoForm.errors.photo" class="text-sm text-danger">{{ photoForm.errors.photo }}</p>
                <button
                    type="submit"
                    :disabled="photoForm.processing || !photoForm.photo"
                    class="w-full btn"
                >
                    Partager ma photo
                </button>
            </form>
            <p v-if="saved === 'photo' || mine.pending_photos > 0" class="notice notice-warning text-center" data-testid="photo-pending">
                Votre photo sera visible après validation par les hôtes.
            </p>
        </section>
    </div>
</template>
