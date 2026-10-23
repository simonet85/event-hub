<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    token: string;
    guestbook: { id: number; author: string; message: string }[];
    mine: { pending_entries: number };
}>();

const entryForm = useForm({ message: '' });

const saved = computed(() => (usePage().props.flash as { contribution_saved?: string } | undefined)?.contribution_saved);

const sendEntry = () => entryForm.post(route('guestbook.store', props.token), { preserveScroll: true, onSuccess: () => entryForm.reset() });
</script>

<template>
    <div class="w-full max-w-md space-y-6 text-left">
        <section id="livre-dor" class="card scroll-mt-4 space-y-4 p-6" data-testid="guestbook">
            <div class="text-center"><p class="eyebrow">{{ __('Livre d\'or') }}</p><h2 class="display-lg mt-1">{{ __('Vos') }} <em>{{ __('mots') }}</em></h2></div>

            <ul v-if="guestbook.length" class="space-y-3">
                <li v-for="e in guestbook" :key="e.id" class="border-b border-line pb-3 last:border-0">
                    <p class="whitespace-pre-line">{{ e.message }}</p>
                    <p class="caption mt-1 text-ink-muted">— {{ e.author }}</p>
                </li>
            </ul>
            <p v-else class="caption text-center text-ink-muted">{{ __('Soyez le premier à laisser un mot.') }}</p>

            <form class="space-y-2" @submit.prevent="sendEntry">
                <textarea
                    v-model="entryForm.message"
                    rows="3"
                    :placeholder="__('Laissez un message aux hôtes…')"
                    class="input"
                />
                <p v-if="entryForm.errors.message" class="text-sm text-danger">{{ entryForm.errors.message }}</p>
                <button
                    type="submit"
                    :disabled="entryForm.processing || !entryForm.message"
                    class="w-full btn"
                >
                    {{ __('Publier mon message') }}
                </button>
            </form>
            <p v-if="saved === 'entry' || mine.pending_entries > 0" class="notice notice-warning text-center" data-testid="entry-pending">
                {{ __('Votre message sera visible après validation par les hôtes.') }}
            </p>
        </section>
    </div>
</template>
