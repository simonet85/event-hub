<script setup lang="ts">
import LocaleSwitcher from '@/Components/ui/LocaleSwitcher.vue';
import { useHostColorScheme } from '@/lib/colorScheme';
import { Link } from '@inertiajs/vue3';

defineProps<{ title?: string; subtitle?: string }>();
useHostColorScheme();
</script>

<template>
    <div class="grid min-h-screen bg-[var(--paper)] text-[var(--ink)] lg:grid-cols-2">
        <a href="#contenu" class="skip-link">{{ __('Aller au contenu') }}</a>
        <aside data-theme="dark" class="relative hidden flex-col justify-between overflow-hidden bg-surface-100 p-12 text-ink lg:flex">
            <Link href="/" class="display text-3xl">{{ __('Event') }} <em class="!text-[var(--gold)]">{{ __('Hub') }}</em></Link>
            <div>
                <p class="eyebrow !text-[var(--gold)]">{{ __('Vous êtes invité(e)') }}</p>
                <p class="display mt-4 text-6xl">{{ __('Vos événements,') }} <em class="!text-[var(--gold)]">{{ __('sublimés.') }}</em></p>
            </div>
            <p class="caption text-ink-muted">{{ __("Invitations · RSVP · Livre d'or · Cagnotte") }}</p>
        </aside>

        <main id="contenu" tabindex="-1" class="relative flex flex-col items-center justify-center px-6 py-12 outline-none">
            <div class="absolute end-6 top-6"><LocaleSwitcher /></div>
            <Link href="/" class="display mb-8 text-3xl lg:hidden">{{ __('Event') }} <em>{{ __('Hub') }}</em></Link>
            <div class="card w-full max-w-md p-6 sm:p-9">
                <div v-if="title" class="mb-7">
                    <h1 class="display text-4xl">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-2 text-sm text-[var(--muted)]">{{ subtitle }}</p>
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>
