<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="grid min-h-screen bg-[var(--paper)] text-[var(--ink)] lg:grid-cols-2">
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-[var(--ink)] p-12 text-[var(--paper)] lg:flex">
            <Link href="/" class="display text-3xl">Event <em class="!text-[var(--gold)]">Hub</em></Link>
            <div>
                <p class="eyebrow !text-[var(--gold)]">Vous êtes invité(e)</p>
                <p class="display mt-4 text-6xl">Vos événements, <em class="!text-[var(--gold)]">sublimés.</em></p>
            </div>
            <p class="text-xs opacity-60">Invitations · RSVP · Livre d'or · Cagnotte</p>
        </aside>

        <div class="flex flex-col items-center justify-center px-6 py-12">
            <Link href="/" class="display mb-8 text-3xl lg:hidden">Event <em>Hub</em></Link>
            <div class="card w-full max-w-md p-6 sm:p-8">
                <slot />
            </div>
        </div>
    </div>
</template>
