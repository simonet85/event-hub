<script setup lang="ts">
import LocaleSwitcher from '@/Components/ui/LocaleSwitcher.vue';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const open = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[var(--paper)] text-[var(--ink)]">
        <a href="#contenu" class="skip-link">Aller au contenu</a>
        <nav class="sticky top-0 z-30 border-b border-[var(--line)] bg-[var(--paper)]/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <div class="flex items-center gap-8">
                    <Link :href="route('events.index')" class="display inline-flex min-h-tap items-center text-2xl">Event <em>Hub</em></Link>
                    <div class="hidden gap-6 text-sm font-semibold sm:flex">
                        <Link
                            :href="route('events.index')"
                            class="border-b-2 py-1"
                            :class="route().current('events.*') ? 'border-[var(--accent)]' : 'border-transparent hover:border-[var(--line)]'"
                        >{{ __('Mes événements') }}</Link>
                        <Link
                            :href="route('profile.edit')"
                            class="border-b-2 py-1"
                            :class="route().current('profile.*') ? 'border-[var(--accent)]' : 'border-transparent hover:border-[var(--line)]'"
                        >{{ __('Profil') }}</Link>
                    </div>
                </div>

                <div class="hidden items-center gap-4 sm:flex">
                    <LocaleSwitcher />
                    <span class="chip">{{ $page.props.auth.user.name }}</span>
                    <Link :href="route('logout')" method="post" as="button" class="text-sm font-semibold hover:underline">{{ __('Déconnexion') }}</Link>
                </div>

                <button
                    type="button"
                    class="inline-flex min-h-tap min-w-tap items-center justify-center rounded-md sm:hidden"
                    :aria-expanded="open"
                    :aria-label="__('Menu')"
                    @click="open = !open"
                >
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path v-if="!open" stroke-linecap="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                        <path v-else stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div v-if="open" class="space-y-1 border-t border-[var(--line)] px-4 py-3 text-sm font-semibold sm:hidden">
                <Link :href="route('events.index')" class="flex min-h-tap items-center">{{ __('Mes événements') }}</Link>
                <Link :href="route('profile.edit')" class="flex min-h-tap items-center">{{ __('Profil') }}</Link>
                <Link :href="route('logout')" method="post" as="button" class="flex min-h-tap w-full items-center">{{ __('Déconnexion') }}</Link>
                <div class="pt-2"><LocaleSwitcher /></div>
                <p class="eyebrow pt-2">{{ $page.props.auth.user.email }}</p>
            </div>
        </nav>

        <header v-if="$slots.header" class="border-b border-[var(--line)]">
            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <slot name="header" />
            </div>
        </header>

        <main id="contenu" tabindex="-1" class="outline-none">
            <slot />
        </main>
    </div>
</template>
