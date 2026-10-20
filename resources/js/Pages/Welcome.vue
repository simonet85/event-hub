<script setup lang="ts">
import Marquee from '@/Components/ui/Marquee.vue';
import Reveal from '@/Components/ui/Reveal.vue';
import ScrollExpandHero from '@/Components/ui/ScrollExpandHero.vue';
import LocaleSwitcher from '@/Components/ui/LocaleSwitcher.vue';
import { translate as __ } from '@/i18n';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
    laravelVersion?: string;
    phpVersion?: string;
}>();

const themes = [
    { name: 'Mariage', accent: '#4e6b48', onAccent: '#fffaf2', paper: '#fbf6ee', ink: '#2a1f17' },
    { name: 'Baptême', accent: '#42698f', onAccent: '#fffaf2', paper: '#fbf6ee', ink: '#2a1f17' },
    { name: 'Anniversaire', accent: '#a0432a', onAccent: '#fffaf2', paper: '#fbf6ee', ink: '#2a1f17' },
    // Le thème Fête s'affiche en mode sombre.
    { name: 'Fête', accent: '#d3a3c8', onAccent: '#1d1711', paper: '#1d1711', ink: '#f4ece0' },
];
const marquee = computed(() => ['Mariages', 'Anniversaires', 'Baptêmes', 'Soirées', 'Cagnotte sécurisée', 'RSVP en ligne'].map((k) => __(k)));
const current = ref(0);
const theme = computed(() => themes[current.value]);
const previewStyle = computed(() => ({
    '--accent': theme.value.accent,
    '--on-accent': theme.value.onAccent,
    backgroundColor: theme.value.paper,
    color: theme.value.ink,
}));

const features = [
    { title: 'Invitations personnelles', text: 'Un lien et un QR code uniques par invité, envoyés par email ou WhatsApp.' },
    { title: 'RSVP en un geste', text: 'Présence, accompagnants, régime alimentaire et chanson préférée.' },
    { title: 'Programme & carte', text: 'Le déroulé de la journée et l\'itinéraire jusqu\'au lieu.' },
    { title: 'Livre d\'or & photos', text: 'Messages et galerie partagée, modérés par les hôtes.' },
    { title: 'Cagnotte & cadeaux', text: 'Paiement mobile sécurisé via MoneyFusion, suivi en temps réel.' },
    { title: 'Tableau de bord', text: 'Réponses, plan de table, exports Excel, CSV et PDF.' },
];

const steps = [
    { n: '01', title: 'Créez', text: 'Choisissez le type d\'événement, la date, le lieu et un thème.' },
    { n: '02', title: 'Invitez', text: 'Importez vos invités, envoyez les invitations en un clic.' },
    { n: '03', title: 'Célébrez', text: 'Suivez les réponses, les photos, les messages et les dons.' },
];
</script>

<template>
    <Head :title="__('Event Hub — vos événements, sublimés')" />

    <div class="min-h-screen overflow-x-clip bg-[var(--paper)] text-[var(--ink)]">
        <a href="#contenu" class="skip-link">{{ __('Aller au contenu') }}</a>
        <div data-theme="dark" class="bg-surface-100 py-2 text-xs tracking-widest text-ink">
            <Marquee :items="marquee" />
        </div>

        <header class="absolute inset-x-0 top-9 z-20 mx-auto flex max-w-6xl items-center justify-between px-5 text-white mix-blend-difference">
            <span class="display text-2xl">{{ __('Event') }} <em class="!text-current">{{ __('Hub') }}</em></span>
            <nav class="flex items-center gap-4 text-sm font-semibold">
                <LocaleSwitcher />
                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="underline">{{ __('Mon espace') }}</Link>
                <template v-else>
                    <Link :href="route('login')" class="hover:underline">{{ __('Connexion') }}</Link>
                    <Link v-if="canRegister" :href="route('register')" class="rounded-full border border-white px-4 py-2">{{ __('Créer un compte') }}</Link>
                </template>
            </nav>
        </header>

        <main id="contenu" tabindex="-1" class="outline-none">
        <ScrollExpandHero>
            <template #cover>
                <div data-theme="dark" class="flex h-full w-full items-center justify-center bg-surface-100 text-ink">
                    <div class="text-center">
                        <p class="eyebrow !text-[var(--gold)]">{{ __('Vous êtes invité(e)') }}</p>
                        <p class="display mt-4 text-6xl sm:text-8xl md:text-9xl">{{ __('Event') }} <em class="!text-[var(--gold)]">{{ __('Hub') }}</em></p>
                        <p class="mt-6 text-xs tracking-[0.3em] text-[var(--gold)]">{{ __('FAITES DÉFILER') }} ↓</p>
                    </div>
                </div>
            </template>
            <template #reveal>
                <div class="mx-auto max-w-3xl px-5 text-center">
                    <p class="eyebrow">{{ __('Le site de votre événement') }}</p>
                    <h1 class="display mt-4 text-5xl sm:text-7xl">
                        {{ __('Vos événements,') }} <em>{{ __('sublimés.') }}</em>
                    </h1>
                    <p class="mx-auto mt-6 max-w-xl text-lg text-[var(--muted)]">
                        {{ __("Invitations, RSVP, programme, livre d'or, photos et cagnotte : tout en un seul endroit, aux couleurs de votre fête.") }}
                    </p>
                    <div class="mt-8 flex flex-wrap justify-center gap-3">
                        <Link v-if="canRegister" :href="route('register')" class="btn">{{ __('Commencer gratuitement') }}</Link>
                        <Link v-if="canLogin" :href="route('login')" class="btn btn-ghost">{{ __('Se connecter') }}</Link>
                    </div>
                </div>
            </template>
        </ScrollExpandHero>

        <section class="mx-auto max-w-6xl px-5 py-24">
            <Reveal>
                <p class="eyebrow">{{ __("Tout ce qu'il faut") }}</p>
                <h2 class="display mt-3 max-w-2xl text-4xl sm:text-5xl">{{ __('Une fête réussie commence par') }} <em>{{ __('un beau site.') }}</em></h2>
            </Reveal>
            <div class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Reveal v-for="(f, i) in features" :key="f.title" :delay="i * 70">
                    <article class="card h-full p-6">
                        <span class="text-[var(--gold)]" aria-hidden="true">✦</span>
                        <h3 class="display mt-3 text-2xl">{{ __(f.title) }}</h3>
                        <p class="mt-2 text-[var(--muted)]">{{ __(f.text) }}</p>
                    </article>
                </Reveal>
            </div>
        </section>

        <section class="bg-[var(--paper-2)] py-24">
            <div class="mx-auto grid max-w-6xl items-center gap-12 px-5 lg:grid-cols-2">
                <Reveal>
                    <p class="eyebrow">{{ __('Choisissez votre ambiance') }}</p>
                    <h2 class="display mt-3 text-4xl sm:text-5xl">{{ __('Des couleurs qui') }} <em>{{ __('vous ressemblent.') }}</em></h2>
                    <p class="mt-4 max-w-md text-[var(--muted)]">{{ __('Chaque événement a son thème : couleurs, police et ambiance. Essayez-en un.') }}</p>
                    <div class="mt-6 flex flex-wrap gap-2" role="group" :aria-label="__('Thème')">
                        <button
                            v-for="(t, i) in themes"
                            :key="t.name"
                            type="button"
                            :aria-pressed="i === current"
                            class="chip cursor-pointer"
                            :class="i === current ? '!border-[var(--ink)] !bg-[var(--ink)] !text-[var(--paper)]' : ''"
                            @click="current = i"
                        >
                            <span class="size-3 rounded-full" :style="{ backgroundColor: t.accent }" />
                            {{ __(t.name) }}
                        </button>
                    </div>
                </Reveal>

                <Reveal :delay="120">
                    <div class="p-8 text-center shadow-card transition-colors duration-500 sm:p-12" :style="previewStyle" data-testid="theme-preview">
                        <p class="eyebrow !text-current">{{ __(theme.name) }}</p>
                        <p class="display mt-3 text-5xl">{{ __('Léa &') }} <em>{{ __('Karim') }}</em></p>
                        <p class="mt-4 text-sm opacity-80">{{ __('Samedi 12 septembre · Domaine des Lilas') }}</p>
                        <div class="btn pointer-events-none mt-6" aria-hidden="true">{{ __('Confirmer ma présence') }}</div>
                    </div>
                </Reveal>
            </div>
        </section>

        <section class="mx-auto max-w-6xl px-5 py-24">
            <Reveal><h2 class="display text-4xl sm:text-5xl">{{ __('Trois étapes,') }} <em>{{ __('zéro stress.') }}</em></h2></Reveal>
            <ol class="mt-12 grid gap-8 md:grid-cols-3">
                <Reveal v-for="(s, i) in steps" :key="s.n" :delay="i * 90">
                    <li class="border-t border-[var(--ink)] pt-5">
                        <span class="display text-5xl text-[var(--gold)]">{{ s.n }}</span>
                        <h3 class="display mt-3 text-2xl">{{ __(s.title) }}</h3>
                        <p class="mt-2 text-[var(--muted)]">{{ __(s.text) }}</p>
                    </li>
                </Reveal>
            </ol>
        </section>

        <section data-theme="dark" class="bg-surface-100 py-20 text-center text-ink">
            <Reveal>
                <h2 class="display mx-auto max-w-2xl px-5 text-4xl sm:text-5xl">{{ __('Prêt à inviter') }} <em class="!text-[var(--gold)]">{{ __('tout le monde ?') }}</em></h2>
                <div class="mt-8 flex justify-center gap-3">
                    <Link v-if="canRegister" :href="route('register')" class="btn">{{ __('Créer mon événement') }}</Link>
                </div>
            </Reveal>
        </section>

        </main>

        <footer class="py-8 text-center text-xs text-[var(--muted)]">Event Hub · {{ __('fait avec soin') }}</footer>
    </div>
</template>
