<script setup lang="ts">
import EmptyState from '@/Components/ui/EmptyState.vue';
import EventHeader from '@/Components/ui/EventHeader.vue';
import Modal from '@/Components/Modal.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    event: { title: string; slug: string; map_missing: boolean; event_date: string; days_left: number };
    rsvp: { total: number; to_invite: number; pending: number; accepted: number; declined: number; headcount: number; response_rate: number };
    dietary: { guest: string; table: string | null; restriction: string; seats: number }[];
    tables: { table: string; guests: number; headcount: number }[];
    privacy: { retention_days: number; purge_on: string; purged_at: string | null; can_purge: boolean };
    caterer_link: { url: string; expires_at: string } | null;
    cagnotte: { raised: number; goal: number | null; pending: number; to_review: number } | null;
    moderation: { entries: number; photos: number };
}>();

const confirmingPurge = ref(false);
const purging = ref(false);
const purgeGuestData = () => {
    purging.value = true;
    router.delete(route('guest-data.purge', props.event.slug), {
        preserveScroll: true,
        onFinish: () => {
            purging.value = false;
            confirmingPurge.value = false;
        },
    });
};
const purged = computed(() => (usePage().props.flash as { guest_data_purged?: boolean } | undefined)?.guest_data_purged === true);

const DAYS = [1, 3, 7, 14, 30];
const days = ref(7);
const copied = ref(false);
const createCatererLink = () => router.post(route('caterer-link.store', props.event.slug), { days: days.value }, { preserveScroll: true, onSuccess: () => (copied.value = false) });
const revokeCatererLink = () => router.delete(route('caterer-link.destroy', props.event.slug), { preserveScroll: true });
const copyCatererLink = async () => {
    if (!props.caterer_link) return;
    try {
        await navigator.clipboard.writeText(props.caterer_link.url);
        copied.value = true;
    } catch {
        copied.value = false; // le champ reste sélectionnable à la main
    }
};
const expiry = (iso: string) => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(iso));

const updated = computed(() => (usePage().props.flash as { event_updated?: boolean | null } | undefined)?.event_updated);

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
                <p v-if="event.map_missing" class="notice notice-warning" data-testid="map-missing">
                    La carte n'a pas encore de position pour cette adresse : la recherche est peut-être en cours.
                    Si ce message persiste, l'adresse est introuvable :
                    <Link :href="route('schedule.index', event.slug)" class="font-semibold underline">ajustez les coordonnées</Link>
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

                <section class="card space-y-3 p-6" data-testid="privacy">
                    <h3 class="display text-2xl">Données des invités</h3>
                    <p v-if="purged" class="notice notice-success" role="status" data-testid="guest-data-purged">Les données des invités ont été supprimées.</p>
                    <p v-if="privacy.purged_at" class="text-sm text-ink-muted" data-testid="privacy-purged">
                        Les données personnelles des invités ont été supprimées le {{ expiry(privacy.purged_at) }}. Les réponses et les montants de la cagnotte restent comptés.
                    </p>
                    <template v-else>
                        <p class="max-w-2xl text-sm text-ink-muted" data-testid="privacy-schedule">
                            Coordonnées, régimes, messages, livre d'or et photos des invités sont supprimés automatiquement {{ privacy.retention_days }} jours après l'événement
                            (le {{ expiry(privacy.purge_on) }}). Les montants des contributions sont conservés. Chaque invité peut aussi supprimer ses données depuis son invitation.
                        </p>
                        <button v-if="privacy.can_purge" type="button" class="btn btn-ghost !px-4 !py-2 text-xs" data-testid="privacy-purge" @click="confirmingPurge = true">Supprimer les données maintenant</button>
                        <p v-else class="text-sm text-ink-muted">La suppression manuelle est possible une fois l'événement passé.</p>
                    </template>

                    <Modal :show="confirmingPurge" max-width="md" @close="confirmingPurge = false">
                        <div class="p-6 sm:p-8">
                            <h2 class="display text-2xl">Supprimer les données des invités ?</h2>
                            <p class="mt-2 text-sm text-ink-muted">
                                Les noms, e-mails, téléphones, régimes, messages, livre d'or et photos seront effacés définitivement, et les invitations cesseront de fonctionner.
                                Pensez à exporter ce dont vous avez besoin (Excel, CSV) avant de continuer.
                            </p>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" class="btn btn-secondary" @click="confirmingPurge = false">Annuler</button>
                                <button type="button" class="btn" :disabled="purging" data-testid="privacy-purge-confirm" @click="purgeGuestData">Supprimer définitivement</button>
                            </div>
                        </div>
                    </Modal>
                </section>

                <section class="card space-y-3 p-6" data-testid="caterer-link">
                    <h3 class="display text-2xl">Lien traiteur</h3>
                    <p class="max-w-2xl text-sm text-ink-muted">
                        Une page en lecture seule (couverts, tables, régimes et allergies, noms abrégés), accessible à toute personne qui a le lien jusqu'à son expiration.
                        Aucune coordonnée d'invité n'y figure.
                    </p>
                    <div v-if="caterer_link" class="space-y-2">
                        <input :value="caterer_link.url" readonly class="input" aria-label="Lien traiteur" data-testid="caterer-url" @focus="($event.target as HTMLInputElement).select()" />
                        <p class="text-sm text-ink-muted">Expire le {{ expiry(caterer_link.expires_at) }}.</p>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="btn btn-ghost !px-4 !py-2 text-xs" @click="copyCatererLink">{{ copied ? 'Lien copié' : 'Copier le lien' }}</button>
                            <button type="button" class="btn btn-ghost !px-4 !py-2 text-xs" data-testid="caterer-revoke" @click="revokeCatererLink">Révoquer le lien</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        <label class="block">
                            <span class="field-label">{{ caterer_link ? 'Nouveau lien valable' : 'Lien valable' }}</span>
                            <select v-model.number="days" class="input !w-auto">
                                <option v-for="d in DAYS" :key="d" :value="d">{{ d }} jour{{ d > 1 ? 's' : '' }}</option>
                            </select>
                        </label>
                        <button type="button" class="btn !px-4 !py-2 text-xs" data-testid="caterer-create" @click="createCatererLink">{{ caterer_link ? 'Remplacer le lien' : 'Créer le lien' }}</button>
                    </div>
                </section>

                <div class="grid gap-8 lg:grid-cols-2">
                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="display text-2xl">Plan de table</h3>
                            <Link :href="route('guests.index', event.slug)" class="inline-flex min-h-tap items-center text-sm font-semibold text-[var(--accent)] hover:underline">Attribuer les tables</Link>
                        </div>
                        <EmptyState v-if="tables.length === 0" class="card" compact icon="table" title="Aucun invité à placer" text="Les tables apparaîtront quand vous aurez ajouté des invités." />
                        <ul v-else class="divide-y card" data-testid="tables">
                            <li v-for="t in tables" :key="t.table" class="flex justify-between p-3 text-sm">
                                <span class="font-medium text-[var(--ink)]">{{ t.table }}</span>
                                <span class="text-[var(--muted)]">{{ t.guests }} invité(s) · {{ t.headcount }} couvert(s)</span>
                            </li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="mb-3 display text-2xl">Régimes alimentaires</h3>
                        <EmptyState v-if="dietary.length === 0" class="card" compact icon="guests" title="Aucun régime particulier signalé" text="Les régimes et allergies indiqués par vos invités apparaîtront ici." />
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
