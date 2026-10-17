<script setup lang="ts">
import LocaleSwitcher from '@/Components/ui/LocaleSwitcher.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{ title?: string; subtitle?: string }>();
</script>

<template>
    <div class="grid min-h-screen bg-[var(--paper)] text-[var(--ink)] lg:grid-cols-2">
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-[var(--ink)] p-12 text-[var(--paper)] lg:flex">
            <Link href="/" class="display text-3xl">Event <em class="!text-[var(--gold)]">Hub</em></Link>
            <div>
                <p class="eyebrow !text-[var(--gold)]">{{ __('Vous êtes invité(e)') }}</p>
                <p class="display mt-4 text-6xl">{{ __('Vos événements,') }} <em class="!text-[var(--gold)]">{{ __('sublimés.') }}</em></p>
            </div>
            <p class="text-xs opacity-60">{{ __("Invitations · RSVP · Livre d'or · Cagnotte") }}</p>
        </aside>

        <div class="relative flex flex-col items-center justify-center px-6 py-12">
            <div class="absolute end-6 top-6"><LocaleSwitcher /></div>
            <Link href="/" class="display mb-8 text-3xl lg:hidden">Event <em>Hub</em></Link>
            <div class="card w-full max-w-md p-6 sm:p-9">
                <div v-if="title" class="mb-7">
                    <h1 class="display text-4xl">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-2 text-sm text-[var(--muted)]">{{ subtitle }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
