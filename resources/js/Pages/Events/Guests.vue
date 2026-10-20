<script setup lang="ts">
import EmptyState from '@/Components/ui/EmptyState.vue';
import EventHeader from '@/Components/ui/EventHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
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
    <Head :title="`${__('Invités')} - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <EventHeader :event="event" :title="__('Invités')" current="guests">
                <template #aside>
                    <button :disabled="pending === 0" class="btn !px-4 !py-2 text-xs" @click="sendAll">{{ __('Envoyer les invitations (:count)', { count: pending }) }}</button>
                </template>
            </EventHeader>
        </template>

        <div class="space-y-8 py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <p v-if="flash?.sent != null" class="notice notice-success">
                    {{ __(':count invitation(s) mise(s) en file d\'envoi.', { count: flash.sent }) }}
                </p>
                <p v-if="flash?.imported != null" class="notice notice-success">
                    {{ __(':count invité(s) importé(s).', { count: flash.imported }) }}
                </p>

                <dl class="grid grid-cols-2 gap-3 text-center sm:grid-cols-5" data-testid="rsvp-summary">
                    <StatCard :label="__('Invités')" :value="summary.total" />
                    <StatCard :label="__('Présents')" :value="summary.accepted" tone="good" />
                    <StatCard :label="__('Absents')" :value="summary.declined" tone="bad" />
                    <StatCard :label="__('En attente')" :value="summary.pending" tone="warn" />
                    <StatCard :label="__('Personnes attendues')" :value="summary.headcount" />
                </dl>

                <form class="grid gap-3 card p-6 sm:grid-cols-5" @submit.prevent="add">
                    <input v-model="form.name" :placeholder="__('Nom')" class="input !w-auto" />
                    <input v-model="form.email" type="email" :placeholder="__('Email')" class="input !w-auto" />
                    <input v-model="form.phone" :placeholder="__('Téléphone (+33...)')" class="input !w-auto" />
                    <input
                        v-model.number="form.max_companions"
                        type="number"
                        min="0"
                        max="10"
                        :title="__('Accompagnants max')"
                        class="input !w-auto"
                    />
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="btn"
                    >
                        {{ __('Ajouter') }}
                    </button>
                    <div class="text-sm text-danger sm:col-span-5">
                        <p v-for="(msg, key) in form.errors" :key="key">{{ msg }}</p>
                    </div>
                </form>

                <form class="flex flex-wrap items-center gap-3 card p-4" @submit.prevent="importCsv">
                    <input
                        type="file"
                        accept=".csv,text/csv"
                        class="block w-full min-w-0 max-w-full text-sm"
                        @change="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                    />
                    <button
                        type="submit"
                        :disabled="!importForm.file || importForm.processing"
                        class="btn btn-secondary"
                    >
                        {{ __('Importer un CSV') }}
                    </button>
                    <p class="caption w-full text-ink-muted">{{ __('Colonnes : name, email, phone, max_companions') }}</p>
                </form>

                <EmptyState v-if="guests.data.length === 0" class="card" icon="guests" :title="__('Aucun invité pour le moment')" :text="__('Ajoutez vos invités un par un ou importez un fichier CSV : chacun recevra son lien d\'invitation personnel.')" />
                <ul v-else class="divide-y card">
                    <li v-for="g in guests.data" :key="g.id" class="flex flex-wrap items-center justify-between gap-2 p-4">
                        <div>
                            <p class="font-medium text-[var(--ink)]">{{ g.name === 'Invité supprimé' ? __('Invité supprimé') : g.name }}</p>
                            <p class="text-sm text-[var(--muted)]">
                                {{ g.email ?? __('pas d\'email') }} · +{{ g.max_companions }} ·
                                {{ g.invited_at ? __('invité') : __('non invité') }} ·
                                <span :class="{ 'text-success': g.rsvp_status === 'accepted', 'text-danger': g.rsvp_status === 'declined' }">
                                    {{ { pending: __('en attente'), accepted: __('présent(e)'), declined: __('absent(e)') }[g.rsvp_status] }}
                                </span>
                                <template v-if="g.rsvp_status === 'accepted' && g.companions_count > 0">
                                    (+{{ g.companions_count }})
                                </template>
                            </p>
                            <p v-if="g.dietary_restrictions" class="text-xs text-[var(--muted)]">{{ __('Régime : :value', { value: g.dietary_restrictions }) }}</p>
                            <p v-if="g.favorite_song" class="text-xs text-[var(--muted)]">{{ __('Chanson : :value', { value: g.favorite_song }) }}</p>
                            <p v-if="g.rsvp_message" class="text-xs italic text-[var(--muted)]">« {{ g.rsvp_message }} »</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm">
                            <input :value="g.table_name ?? ''" maxlength="100" :placeholder="__('Table')" :aria-label="__('Table')" class="input !w-28 text-sm" @change="saveTable(g, ($event.target as HTMLInputElement).value)" />
                            <a :href="g.invitation_url" target="_blank" class="inline-flex min-h-tap items-center font-semibold text-[var(--accent)] hover:underline">{{ __('Voir') }}</a>
                            <button v-if="g.email" class="inline-flex min-h-tap items-center font-semibold text-[var(--accent)] hover:underline" @click="send(g)">{{ __('Envoyer par email') }}</button>
                            <a v-if="g.whatsapp_url" :href="g.whatsapp_url" target="_blank" class="inline-flex min-h-tap items-center text-success">
                                {{ __('WhatsApp') }}
                            </a>
                            <button class="inline-flex min-h-tap items-center text-danger" @click="remove(g)">{{ __('Supprimer') }}</button>
                        </div>
                    </li>
                </ul>
                <nav v-if="guests.links.length > 3" class="flex flex-wrap justify-center gap-1" :aria-label="__('Pagination')">
                    <template v-for="(l, i) in guests.links" :key="i">
                        <Link
                            v-if="l.url"
                            :href="l.url"
                            preserve-scroll
                            class="rounded border px-3 py-1 text-sm"
                            :class="l.active ? 'bg-[var(--ink)] text-[var(--paper)]' : 'bg-[var(--paper-2)]'"
                            v-html="l.label"
                        />
                        <span v-else class="px-3 py-1 text-sm text-[var(--muted)]" v-html="l.label" />
                    </template>
                </nav>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
