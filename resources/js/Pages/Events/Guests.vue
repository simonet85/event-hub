<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type Guest = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    max_companions: number;
    invited_at: string | null;
    table_name: string | null;
    rsvp_status: 'pending' | 'accepted' | 'declined';
    companions_count: number;
    dietary_restrictions: string | null;
    favorite_song: string | null;
    rsvp_message: string | null;
    invitation_url: string;
    whatsapp_url: string | null;
};

const props = defineProps<{
    event: { title: string; slug: string };
    summary: { total: number; to_invite: number; pending: number; accepted: number; declined: number; headcount: number };
    guests: { data: Guest[]; links: { url: string | null; label: string; active: boolean }[] };
}>();

const flash = computed(() => usePage().props.flash as { sent?: number; imported?: number } | undefined);

const form = useForm({ name: '', email: '', phone: '', max_companions: 0 });
const importForm = useForm<{ file: File | null }>({ file: null });

const add = () => form.post(route('guests.store', props.event.slug), { onSuccess: () => form.reset() });
const importCsv = () =>
    importForm.post(route('guests.import', props.event.slug), { onSuccess: () => importForm.reset() });
const sendAll = () => router.post(route('invitations.send-all', props.event.slug));
const send = (g: Guest) => router.post(route('invitations.send', [props.event.slug, g.id]));
const saveTable = (g: Guest, value: string) => {
    if ((g.table_name ?? '') === value.trim()) return;
    router.patch(route('guests.update', [props.event.slug, g.id]), { table_name: value }, { preserveScroll: true });
};
const remove = (g: Guest) => router.delete(route('guests.destroy', [props.event.slug, g.id]));
const pending = computed(() => props.summary.to_invite);
</script>

<template>
    <Head :title="`Invités - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="display text-3xl sm:text-4xl">Invités — {{ event.title }}</h2>
                <button
                    :disabled="pending === 0"
                    class="btn disabled:opacity-40"
                    @click="sendAll"
                >
                    Envoyer les invitations ({{ pending }})
                </button>
            </div>
        </template>

        <div class="space-y-8 py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <p v-if="flash?.sent != null" class="rounded bg-green-50 p-3 text-green-800">
                    {{ flash.sent }} invitation(s) mise(s) en file d'envoi.
                </p>
                <p v-if="flash?.imported != null" class="rounded bg-green-50 p-3 text-green-800">
                    {{ flash.imported }} invité(s) importé(s).
                </p>

                <dl class="grid grid-cols-2 gap-3 text-center sm:grid-cols-5" data-testid="rsvp-summary">
                    <div class="card p-3"><dt class="text-xs text-[var(--muted)]">Invités</dt><dd class="text-2xl font-semibold">{{ summary.total }}</dd></div>
                    <div class="card p-3"><dt class="text-xs text-[var(--muted)]">Présents</dt><dd class="text-2xl font-semibold text-green-700">{{ summary.accepted }}</dd></div>
                    <div class="card p-3"><dt class="text-xs text-[var(--muted)]">Absents</dt><dd class="text-2xl font-semibold text-red-700">{{ summary.declined }}</dd></div>
                    <div class="card p-3"><dt class="text-xs text-[var(--muted)]">En attente</dt><dd class="text-2xl font-semibold text-amber-600">{{ summary.pending }}</dd></div>
                    <div class="card p-3"><dt class="text-xs text-[var(--muted)]">Personnes attendues</dt><dd class="text-2xl font-semibold">{{ summary.headcount }}</dd></div>
                </dl>

                <form class="grid gap-3 card p-6 sm:grid-cols-5" @submit.prevent="add">
                    <input v-model="form.name" placeholder="Nom" class="rounded-md border-gray-300" />
                    <input v-model="form.email" type="email" placeholder="Email" class="rounded-md border-gray-300" />
                    <input v-model="form.phone" placeholder="Téléphone (+33...)" class="rounded-md border-gray-300" />
                    <input
                        v-model.number="form.max_companions"
                        type="number"
                        min="0"
                        max="10"
                        title="Accompagnants max"
                        class="rounded-md border-gray-300"
                    />
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-white disabled:opacity-50"
                    >
                        Ajouter
                    </button>
                    <div class="text-sm text-red-600 sm:col-span-5">
                        <p v-for="(msg, key) in form.errors" :key="key">{{ msg }}</p>
                    </div>
                </form>

                <form class="flex items-center gap-3 card p-4" @submit.prevent="importCsv">
                    <input
                        type="file"
                        accept=".csv,text/csv"
                        @change="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                    />
                    <button
                        type="submit"
                        :disabled="!importForm.file || importForm.processing"
                        class="rounded-md border px-3 py-1 text-sm disabled:opacity-40"
                    >
                        Importer un CSV (name, email, phone, max_companions)
                    </button>
                </form>

                <p v-if="guests.data.length === 0" class="text-[var(--muted)]">Aucun invité pour le moment.</p>
                <ul v-else class="divide-y card">
                    <li v-for="g in guests.data" :key="g.id" class="flex flex-wrap items-center justify-between gap-2 p-4">
                        <div>
                            <p class="font-medium text-[var(--ink)]">{{ g.name }}</p>
                            <p class="text-sm text-[var(--muted)]">
                                {{ g.email ?? 'pas d\'email' }} · +{{ g.max_companions }} ·
                                {{ g.invited_at ? 'invité' : 'non invité' }} ·
                                <span :class="{ 'text-green-700': g.rsvp_status === 'accepted', 'text-red-700': g.rsvp_status === 'declined' }">
                                    {{ { pending: 'en attente', accepted: 'présent(e)', declined: 'absent(e)' }[g.rsvp_status] }}
                                </span>
                                <template v-if="g.rsvp_status === 'accepted' && g.companions_count > 0">
                                    (+{{ g.companions_count }})
                                </template>
                            </p>
                            <p v-if="g.dietary_restrictions" class="text-xs text-[var(--muted)]">Régime : {{ g.dietary_restrictions }}</p>
                            <p v-if="g.favorite_song" class="text-xs text-[var(--muted)]">Chanson : {{ g.favorite_song }}</p>
                            <p v-if="g.rsvp_message" class="text-xs italic text-[var(--muted)]">« {{ g.rsvp_message }} »</p>
                        </div>
                        <div class="flex items-center gap-3 text-sm">
                            <input :value="g.table_name ?? ''" maxlength="100" placeholder="Table" aria-label="Table" class="w-28 rounded-md border-gray-300 py-1 text-sm" @change="saveTable(g, ($event.target as HTMLInputElement).value)" />
                            <a :href="g.invitation_url" target="_blank" class="font-semibold text-[var(--accent)] hover:underline">Voir</a>
                            <button v-if="g.email" class="font-semibold text-[var(--accent)] hover:underline" @click="send(g)">Envoyer par email</button>
                            <a v-if="g.whatsapp_url" :href="g.whatsapp_url" target="_blank" class="text-green-700">
                                WhatsApp
                            </a>
                            <button class="text-red-600" @click="remove(g)">Supprimer</button>
                        </div>
                    </li>
                </ul>
                <nav v-if="guests.links.length > 3" class="flex flex-wrap justify-center gap-1" aria-label="Pagination">
                    <template v-for="(l, i) in guests.links" :key="i">
                        <Link
                            v-if="l.url"
                            :href="l.url"
                            preserve-scroll
                            class="rounded border px-3 py-1 text-sm"
                            :class="l.active ? 'bg-[var(--ink)] text-[var(--paper)]' : 'bg-white/70'"
                            v-html="l.label"
                        />
                        <span v-else class="px-3 py-1 text-sm text-[var(--muted)]" v-html="l.label" />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
