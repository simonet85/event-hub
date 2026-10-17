<script setup lang="ts">
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

const fcfa = (n: number) => `${new Intl.NumberFormat('fr-FR').format(n)} FCFA`;
const day = (iso: string) => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(iso));

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
    ({ confirmed: 'text-green-700', pending: 'text-amber-600', mismatch: 'text-red-700', failed: 'text-gray-500', expired: 'text-gray-500' })[status] ?? '';
</script>

<template>
    <Head :title="`Cagnotte - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Cagnotte &amp; cadeaux — {{ event.title }}</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-8 px-4 sm:px-6 lg:px-8">
                <dl class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4" data-testid="stats">
                    <div class="rounded-lg bg-white p-3 shadow-sm"><dt class="text-xs text-gray-500">Collecté (confirmé)</dt><dd class="text-xl font-semibold text-green-700">{{ fcfa(stats.raised) }}</dd></div>
                    <div class="rounded-lg bg-white p-3 shadow-sm"><dt class="text-xs text-gray-500">Contributions</dt><dd class="text-xl font-semibold">{{ stats.confirmed }}</dd></div>
                    <div class="rounded-lg bg-white p-3 shadow-sm"><dt class="text-xs text-gray-500">En attente</dt><dd class="text-xl font-semibold text-amber-600">{{ stats.pending }}</dd></div>
                    <div class="rounded-lg bg-white p-3 shadow-sm"><dt class="text-xs text-gray-500">À vérifier</dt><dd class="text-xl font-semibold text-red-700">{{ stats.to_review }}</dd></div>
                </dl>

                <form class="space-y-3 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="saveSettings">
                    <h3 class="font-semibold text-gray-900">Réglages de la cagnotte</h3>
                    <label class="flex items-center gap-2">
                        <input v-model="settings.cagnotte_enabled" type="checkbox" class="rounded border-gray-300" />
                        <span class="text-sm text-gray-700">Activer la cagnotte et la liste de cadeaux pour les invités</span>
                    </label>
                    <input v-model.number="settings.cagnotte_goal" type="number" min="100" placeholder="Objectif en FCFA (optionnel)" class="block w-full rounded-md border-gray-300" />
                    <textarea v-model="settings.cagnotte_message" rows="2" placeholder="Message aux invités (optionnel)" class="block w-full rounded-md border-gray-300" />
                    <p v-for="(msg, key) in settings.errors" :key="key" class="text-sm text-red-600">{{ msg }}</p>
                    <p v-if="saved" class="text-sm text-green-700">Enregistré.</p>
                    <button type="submit" :disabled="settings.processing" class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white disabled:opacity-50">Enregistrer</button>
                </form>

                <form class="space-y-3 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="addGift">
                    <h3 class="font-semibold text-gray-900">Ajouter un cadeau</h3>
                    <input v-model="giftForm.name" placeholder="Nom (ex : Voyage de noces)" class="block w-full rounded-md border-gray-300" />
                    <input v-model="giftForm.description" placeholder="Description (optionnel)" class="block w-full rounded-md border-gray-300" />
                    <input v-model.number="giftForm.target_amount" type="number" min="100" placeholder="Montant cible en FCFA" class="block w-full rounded-md border-gray-300" />
                    <p v-for="(msg, key) in giftForm.errors" :key="key" class="text-sm text-red-600">{{ msg }}</p>
                    <button type="submit" :disabled="giftForm.processing" class="rounded-md bg-indigo-600 px-4 py-2 text-white disabled:opacity-50">Ajouter</button>
                </form>

                <ul v-if="gifts.length" class="divide-y rounded-lg bg-white shadow-sm" data-testid="gift-list">
                    <li v-for="g in gifts" :key="g.id" class="flex items-center justify-between gap-3 p-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ g.name }}</p>
                            <p class="text-sm text-gray-500">{{ fcfa(g.raised) }} / {{ fcfa(g.target_amount) }}</p>
                        </div>
                        <button class="text-sm text-red-600" @click="removeGift(g.id)">Supprimer</button>
                    </li>
                </ul>

                <section>
                    <h3 class="mb-3 text-lg font-semibold text-gray-900">Contributions</h3>
                    <p v-if="contributions.data.length === 0" class="text-gray-600">Aucune contribution pour le moment.</p>
                    <ul v-else class="divide-y rounded-lg bg-white shadow-sm">
                        <li v-for="c in contributions.data" :key="c.id" class="flex flex-wrap items-start justify-between gap-2 p-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ c.guest }} — {{ fcfa(c.amount) }}<span v-if="c.gift" class="font-normal text-gray-500"> · {{ c.gift }}</span></p>
                                <p v-if="c.message" class="text-sm italic text-gray-500">« {{ c.message }} »</p>
                                <p class="text-xs text-gray-400">{{ day(c.created_at) }}</p>
                            </div>
                            <span class="text-sm font-medium" :class="badge(c.status)">{{ c.status_label }}</span>
                        </li>
                    </ul>
                    <nav v-if="contributions.links.length > 3" class="mt-4 flex flex-wrap justify-center gap-1" aria-label="Pagination">
                        <template v-for="(l, i) in contributions.links" :key="i">
                            <Link v-if="l.url" :href="l.url" preserve-scroll class="rounded border px-3 py-1 text-sm" :class="l.active ? 'bg-gray-800 text-white' : 'bg-white'" v-html="l.label" />
                            <span v-else class="px-3 py-1 text-sm text-gray-400" v-html="l.label" />
                        </template>
                    </nav>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
