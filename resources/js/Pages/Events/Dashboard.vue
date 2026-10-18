<script setup lang="ts">
import EventHeader from '@/Components/ui/EventHeader.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    event: { title: string; slug: string; event_date: string; days_left: number };
    rsvp: { total: number; to_invite: number; pending: number; accepted: number; declined: number; headcount: number; response_rate: number };
    dietary: { guest: string; table: string | null; restriction: string; seats: number }[];
    tables: { table: string; guests: number; headcount: number }[];
    cagnotte: { raised: number; goal: number | null; pending: number; to_review: number } | null;
    moderation: { entries: number; photos: number };
}>();

const flash = computed(() => usePage().props.flash as { event_updated?: boolean | null; map_not_found?: boolean | null } | undefined);
const updated = computed(() => flash.value?.event_updated);
const mapNotFound = computed(() => flash.value?.map_not_found);

const fcfa = (n: number) => `${new Intl.NumberFormat('fr-FR').format(n)} FCFA`;
const percent = (a: number, b: number) => (b > 0 ? Math.min(100, Math.round((a / b) * 100)) : 0);
</script>

<template>
    <Head :title="`Tableau de bord - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <EventHeader :event="event" title="Tableau de bord" current="dashboard">
                <template #aside>
                    <p class="chip" data-testid="days-left">{{ event.days_left === 0 ? 'C\'est aujourd\'hui !' : `J-${event.days_left}` }}</p>
                </template>
            </EventHeader>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <p v-if="updated" class="notice notice-success" data-testid="event-updated">Les modifications ont été enregistrées.</p>
                <p v-if="mapNotFound" class="notice notice-warning" data-testid="map-not-found">
                    Adresse introuvable sur la carte : la position précédente est conservée.
                    <Link :href="route('schedule.index', event.slug)" class="font-semibold underline">Ajustez les coordonnées</Link>
                    dans l'onglet Programme.
                </p>
                <section>
                    <h3 class="mb-3 display text-2xl">Réponses des invités</h3>
                    <dl class="grid grid-cols-2 gap-3 text-center sm:grid-cols-3 lg:grid-cols-6" data-testid="rsvp">
                        <StatCard label="Invités" :value="rsvp.total" />
                        <StatCard label="Présents" :value="rsvp.accepted" tone="good" />
                        <StatCard label="Absents" :value="rsvp.declined" tone="bad" />
                        <StatCard label="En attente" :value="rsvp.pending" tone="warn" />
                        <StatCard label="Couverts attendus" :value="rsvp.headcount" />
                        <StatCard label="Taux de réponse" :value="`${rsvp.response_rate} %`" />
                    </dl>
                    <p v-if="rsvp.to_invite > 0" class="mt-2 text-sm text-warning">
                        {{ rsvp.to_invite }} invité(s) n'ont pas encore reçu leur invitation.
                        <Link :href="route('guests.index', event.slug)" class="inline-flex min-h-tap items-center underline">Envoyer</Link>
                    </p>
                </section>

                <section class="card p-6">
                    <h3 class="mb-3 display text-2xl">Exports</h3>
                    <div class="flex flex-wrap gap-3 text-sm" data-testid="exports">
                        <a :href="route('exports.xlsx', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">Excel (invités, régimes, plan de table)</a>
                        <a :href="route('exports.csv', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">CSV des invités</a>
                        <a :href="route('exports.caterer', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">PDF traiteur</a>
                        <a :href="route('exports.seating', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">PDF plan de table</a>
                    </div>
                </section>

                <div class="grid gap-8 lg:grid-cols-2">
                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="display text-2xl">Plan de table</h3>
                            <Link :href="route('guests.index', event.slug)" class="inline-flex min-h-tap items-center text-sm font-semibold text-[var(--accent)] hover:underline">Attribuer les tables</Link>
                        </div>
                        <p v-if="tables.length === 0" class="text-[var(--muted)]">Aucun invité à placer.</p>
                        <ul v-else class="divide-y card" data-testid="tables">
                            <li v-for="t in tables" :key="t.table" class="flex justify-between p-3 text-sm">
                                <span class="font-medium text-[var(--ink)]">{{ t.table }}</span>
                                <span class="text-[var(--muted)]">{{ t.guests }} invité(s) · {{ t.headcount }} couvert(s)</span>
                            </li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="mb-3 display text-2xl">Régimes alimentaires</h3>
                        <p v-if="dietary.length === 0" class="text-[var(--muted)]">Aucun régime particulier signalé.</p>
                        <ul v-else class="divide-y card" data-testid="dietary">
                            <li v-for="d in dietary" :key="d.guest + d.restriction" class="p-3 text-sm">
                                <span class="font-medium text-[var(--ink)]">{{ d.guest }}</span>
                                <span v-if="d.table" class="text-[var(--muted)]"> · {{ d.table }}</span>
                                <p class="text-[var(--muted)]">{{ d.restriction }}</p>
                            </li>
                        </ul>
                    </section>
                </div>

                <section v-if="cagnotte" class="card p-6" data-testid="cagnotte">
                    <div class="flex items-center justify-between">
                        <h3 class="display text-2xl">Cagnotte</h3>
                        <Link :href="route('cagnotte.index', event.slug)" class="inline-flex min-h-tap items-center text-sm font-semibold text-[var(--accent)] hover:underline">Détail</Link>
                    </div>
                    <p class="mt-1 text-2xl font-semibold text-success">{{ fcfa(cagnotte.raised) }}</p>
                    <div v-if="cagnotte.goal" class="mt-2">
                        <div class="h-2 overflow-hidden rounded-full bg-[var(--line)]"><div class="h-full bg-[var(--accent)]" :style="{ width: percent(cagnotte.raised, cagnotte.goal) + '%' }" /></div>
                        <p class="mt-1 text-xs text-[var(--muted)]">objectif {{ fcfa(cagnotte.goal) }} ({{ percent(cagnotte.raised, cagnotte.goal) }} %)</p>
                    </div>
                    <p class="mt-2 text-sm text-[var(--muted)]">
                        {{ cagnotte.pending }} en attente<span v-if="cagnotte.to_review > 0" class="text-danger"> · {{ cagnotte.to_review }} à vérifier</span>
                    </p>
                </section>

                <section v-if="moderation.entries + moderation.photos > 0" class="notice notice-warning" data-testid="moderation">
                    {{ moderation.entries }} message(s) et {{ moderation.photos }} photo(s) attendent votre validation.
                    <Link :href="route('moderation.index', event.slug)" class="inline-flex min-h-tap items-center underline">Modérer</Link>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
