<script setup lang="ts">
import { formatTime, intlLocale } from '@/lib/locale';
import EmptyState from '@/Components/ui/EmptyState.vue';
import EventHeader from '@/Components/ui/EventHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    event: { title: string; slug: string; cagnotte_enabled: boolean; cagnotte_goal: number | null; cagnotte_message: string | null };
    stats: { raised: number; confirmed: number; pending: number; to_review: number };
    gifts: { id: number; name: string; description: string | null; target_amount: number; raised: number }[];
    contributions: {
        data: { id: number; guest: string; gift: string | null; amount: number; message: string | null; status: string; status_label: string; created_at: string }[];
        links: { url: string | null; label: string; active: boolean }[];
    };
}>();

const fcfa = (n: number) => `${new Intl.NumberFormat(intlLocale()).format(n)} FCFA`;
const day = (iso: string) => new Intl.DateTimeFormat(intlLocale(), { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));

const settings = useForm({
    cagnotte_enabled: props.event.cagnotte_enabled,
    cagnotte_goal: props.event.cagnotte_goal as number | null,
    cagnotte_message: props.event.cagnotte_message ?? '',
});
const giftForm = useForm({ name: '', description: '', target_amount: null as number | null });

const saved = computed(() => (usePage().props.flash as { cagnotte_saved?: boolean } | undefined)?.cagnotte_saved === true);
const saveSettings = () => settings.patch(route('cagnotte.settings', props.event.slug), { preserveScroll: true });
const addGift = () => giftForm.post(route('gifts.store', props.event.slug), { preserveScroll: true, onSuccess: () => giftForm.reset() });
const removeGift = (id: number) => router.delete(route('gifts.destroy', [props.event.slug, id]), { preserveScroll: true });
const badge = (status: string) =>
    ({ confirmed: 'text-success', pending: 'text-warning', mismatch: 'text-danger', failed: 'text-[var(--muted)]', expired: 'text-[var(--muted)]' })[status] ?? '';
</script>

<template>
    <Head :title="`${__('Cagnotte')} - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <EventHeader :event="event" :title="__('Cagnotte & cadeaux')" current="cagnotte"></EventHeader>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <dl class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4" data-testid="stats">
                    <StatCard :label="__('Collecté (confirmé)')" :value="fcfa(stats.raised)" tone="good" />
                    <StatCard :label="__('Contributions')" :value="stats.confirmed" />
                    <StatCard :label="__('En attente')" :value="stats.pending" tone="warn" />
                    <StatCard :label="__('À vérifier')" :value="stats.to_review" tone="bad" />
                </dl>

                <form class="space-y-3 card p-6" @submit.prevent="saveSettings">
                    <h3 class="display text-2xl">{{ __('Réglages de la cagnotte') }}</h3>
                    <label class="flex items-center gap-2">
                        <input v-model="settings.cagnotte_enabled" type="checkbox" class="check" />
                        <span class="text-sm text-[var(--ink)]">{{ __('Activer la cagnotte et la liste de cadeaux pour les invités') }}</span>
                    </label>
                    <input v-model.number="settings.cagnotte_goal" type="number" min="100" :placeholder="__('Objectif en FCFA (optionnel)')" class="input" />
                    <textarea v-model="settings.cagnotte_message" rows="2" :placeholder="__('Message aux invités (optionnel)')" class="input" />
                    <p v-for="(msg, key) in settings.errors" :key="key" class="text-sm text-danger">{{ msg }}</p>
                    <p v-if="saved" class="text-sm text-success">{{ __('Enregistré.') }}</p>
                    <button type="submit" :disabled="settings.processing" class="btn disabled:opacity-50">{{ __('Enregistrer') }}</button>
                </form>

                <form class="space-y-3 card p-6" @submit.prevent="addGift">
                    <h3 class="display text-2xl">{{ __('Ajouter un cadeau') }}</h3>
                    <input v-model="giftForm.name" :placeholder="__('Nom (ex : Voyage de noces)')" class="input" />
                    <input v-model="giftForm.description" :placeholder="__('Description (optionnel)')" class="input" />
                    <input v-model.number="giftForm.target_amount" type="number" min="100" :placeholder="__('Montant cible en FCFA')" class="input" />
                    <p v-for="(msg, key) in giftForm.errors" :key="key" class="text-sm text-danger">{{ msg }}</p>
                    <button type="submit" :disabled="giftForm.processing" class="btn">{{ __('Ajouter') }}</button>
                </form>

                <ul v-if="gifts.length" class="divide-y card" data-testid="gift-list">
                    <li v-for="g in gifts" :key="g.id" class="flex items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-[var(--ink)]">{{ g.name }}</p>
                            <p class="text-sm text-[var(--muted)]">{{ fcfa(g.raised) }} / {{ fcfa(g.target_amount) }}</p>
                        </div>
                        <button class="text-sm text-danger" @click="removeGift(g.id)">{{ __('Supprimer') }}</button>
                    </li>
                </ul>

                <section>
                    <h3 class="mb-3 display text-2xl">{{ __('Contributions') }}</h3>
                    <EmptyState v-if="contributions.data.length === 0" class="card" icon="gift" :title="__('Aucune contribution pour le moment')" :text="__('Les participations de vos invités apparaîtront ici dès qu\'elles seront confirmées.')" />
                    <ul v-else class="divide-y card">
                        <li v-for="c in contributions.data" :key="c.id" class="flex flex-wrap items-start justify-between gap-2 p-4">
                            <div>
                                <p class="font-medium text-[var(--ink)]">{{ c.guest }} — {{ fcfa(c.amount) }}<span v-if="c.gift" class="font-normal text-[var(--muted)]"> · {{ c.gift }}</span></p>
                                <p v-if="c.message" class="text-sm italic text-[var(--muted)]">« {{ c.message }} »</p>
                                <p class="text-xs text-[var(--muted)]">{{ day(c.created_at) }}</p>
                            </div>
                            <span class="text-sm font-medium" :class="badge(c.status)">{{ c.status_label }}</span>
                        </li>
                    </ul>
                    <nav v-if="contributions.links.length > 3" class="mt-4 flex flex-wrap justify-center gap-1" :aria-label="__('Pagination')">
                        <template v-for="(l, i) in contributions.links" :key="i">
                            <Link v-if="l.url" :href="l.url" preserve-scroll class="rounded border px-3 py-1 text-sm" :class="l.active ? 'bg-[var(--ink)] text-[var(--paper)]' : 'bg-[var(--paper-2)]'" v-html="l.label" />
                            <span v-else class="px-3 py-1 text-sm text-[var(--muted)]" v-html="l.label" />
                        </template>
                    </nav>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
