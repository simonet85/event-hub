<script setup lang="ts">
import { formatTime, intlLocale } from '@/lib/locale';
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
const expiry = (iso: string) => new Intl.DateTimeFormat(intlLocale(), { dateStyle: 'long', timeStyle: 'short' }).format(new Date(iso));

const updated = computed(() => (usePage().props.flash as { event_updated?: boolean | null } | undefined)?.event_updated);

const fcfa = (n: number) => `${new Intl.NumberFormat(intlLocale()).format(n)} FCFA`;
const percent = (a: number, b: number) => (b > 0 ? Math.min(100, Math.round((a / b) * 100)) : 0);
</script>

<template>
    <Head :title="`Tableau de bord - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <EventHeader :event="event" :title="__('Tableau de bord')" current="dashboard">
                <template #aside>
                    <p class="chip" data-testid="days-left">{{ event.days_left === 0 ? __('C\'est aujourd\'hui !') : `${__('J-')}${event.days_left}` }}</p>
                </template>
            </EventHeader>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <p v-if="updated" class="notice notice-success" data-testid="event-updated">{{ __('Les modifications ont été enregistrées.') }}</p>
                <p v-if="event.map_missing" class="notice notice-warning" data-testid="map-missing">
                    {{ __('La carte n\'a pas encore de position pour cette adresse : la recherche est peut-être en cours. Si ce message persiste, l\'adresse est introuvable :') }}
                    <Link :href="route('schedule.index', event.slug)" class="font-semibold underline">{{ __('ajustez les coordonnées') }}</Link>
                    {{ __('dans l\'onglet Programme.') }}
                </p>
                <section>
                    <h3 class="mb-3 display text-2xl">{{ __('Réponses des invités') }}</h3>
                    <dl class="grid grid-cols-2 gap-3 text-center sm:grid-cols-3 lg:grid-cols-6" data-testid="rsvp">
                        <StatCard :label="__('Invités')" :value="rsvp.total" />
                        <StatCard :label="__('Présents')" :value="rsvp.accepted" tone="good" />
                        <StatCard :label="__('Absents')" :value="rsvp.declined" tone="bad" />
                        <StatCard :label="__('En attente')" :value="rsvp.pending" tone="warn" />
                        <StatCard :label="__('Couverts attendus')" :value="rsvp.headcount" />
                        <StatCard :label="__('Taux de réponse')" :value="`${rsvp.response_rate} %`" />
                    </dl>
                    <p v-if="rsvp.to_invite > 0" class="mt-2 text-sm text-warning">
                        {{ __(':count invité(s) n\'ont pas encore reçu leur invitation.', { count: rsvp.to_invite }) }}
                        <Link :href="route('guests.index', event.slug)" class="inline-flex min-h-tap items-center underline">{{ __('Envoyer') }}</Link>
                    </p>
                </section>

                <section class="card p-6">
                    <h3 class="mb-3 display text-2xl">{{ __('Exports') }}</h3>
                    <div class="flex flex-wrap gap-3 text-sm" data-testid="exports">
                        <a :href="route('exports.xlsx', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">{{ __('Excel (invités, régimes, plan de table)') }}</a>
                        <a :href="route('exports.csv', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">{{ __('CSV des invités') }}</a>
                        <a :href="route('exports.caterer', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">{{ __('PDF traiteur') }}</a>
                        <a :href="route('exports.seating', event.slug)" class="btn btn-ghost !px-4 !py-2 text-xs">{{ __('PDF plan de table') }}</a>
                    </div>
                </section>

                <section class="card space-y-3 p-6" data-testid="privacy">
                    <h3 class="display text-2xl">{{ __('Données des invités') }}</h3>
                    <p v-if="purged" class="notice notice-success" role="status" data-testid="guest-data-purged">{{ __('Les données des invités ont été supprimées.') }}</p>
                    <p v-if="privacy.purged_at" class="text-sm text-ink-muted" data-testid="privacy-purged">
                        {{ __('Les données personnelles des invités ont été supprimées le :date. Les réponses et les montants de la cagnotte restent comptés.', { date: expiry(privacy.purged_at) }) }}
                    </p>
                    <template v-else>
                        <p class="max-w-2xl text-sm text-ink-muted" data-testid="privacy-schedule">
                            {{ __('Coordonnées, régimes, messages, livre d\'or et photos des invités sont supprimés automatiquement :days jours après l\'événement (le :date). Les montants des contributions sont conservés. Chaque invité peut aussi supprimer ses données depuis son invitation.', { days: privacy.retention_days, date: expiry(privacy.purge_on) }) }}
                        </p>
                        <button v-if="privacy.can_purge" type="button" class="btn btn-ghost !px-4 !py-2 text-xs" data-testid="privacy-purge" @click="confirmingPurge = true">{{ __('Supprimer les données maintenant') }}</button>
                        <p v-else class="text-sm text-ink-muted">{{ __('La suppression manuelle est possible une fois l\'événement passé.') }}</p>
                    </template>

                    <Modal :show="confirmingPurge" max-width="md" @close="confirmingPurge = false">
                        <div class="p-6 sm:p-8">
                            <h2 class="display text-2xl">{{ __('Supprimer les données des invités ?') }}</h2>
                            <p class="mt-2 text-sm text-ink-muted">
                                {{ __('Les noms, e-mails, téléphones, régimes, messages, livre d\'or et photos seront effacés définitivement, et les invitations cesseront de fonctionner. Pensez à exporter ce dont vous avez besoin (Excel, CSV) avant de continuer.') }}
                            </p>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" class="btn btn-secondary" @click="confirmingPurge = false">{{ __('Annuler') }}</button>
                                <button type="button" class="btn" :disabled="purging" data-testid="privacy-purge-confirm" @click="purgeGuestData">{{ __('Supprimer définitivement') }}</button>
                            </div>
                        </div>
                    </Modal>
                </section>

                <section class="card space-y-3 p-6" data-testid="caterer-link">
                    <h3 class="display text-2xl">{{ __('Lien traiteur') }}</h3>
                    <p class="max-w-2xl text-sm text-ink-muted">
                        {{ __('Une page en lecture seule (couverts, tables, régimes et allergies, noms abrégés), accessible à toute personne qui a le lien jusqu\'à son expiration. Aucune coordonnée d\'invité n\'y figure.') }}
                    </p>
                    <div v-if="caterer_link" class="space-y-2">
                        <input :value="caterer_link.url" readonly class="input" :aria-label="__('Lien traiteur')" data-testid="caterer-url" @focus="($event.target as HTMLInputElement).select()" />
                        <p class="text-sm text-ink-muted">{{ __('Expire le :date.', { date: expiry(caterer_link.expires_at) }) }}</p>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="btn btn-ghost !px-4 !py-2 text-xs" @click="copyCatererLink">{{ copied ? __('Lien copié') : __('Copier le lien') }}</button>
                            <button type="button" class="btn btn-ghost !px-4 !py-2 text-xs" data-testid="caterer-revoke" @click="revokeCatererLink">{{ __('Révoquer le lien') }}</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        <label class="block">
                            <span class="field-label">{{ caterer_link ? __('Nouveau lien valable') : __('Lien valable') }}</span>
                            <select v-model.number="days" class="input !w-auto">
                                <option v-for="d in DAYS" :key="d" :value="d">{{ d > 1 ? __(':count jours', { count: d }) : __(':count jour', { count: d }) }}</option>
                            </select>
                        </label>
                        <button type="button" class="btn !px-4 !py-2 text-xs" data-testid="caterer-create" @click="createCatererLink">{{ caterer_link ? __('Remplacer le lien') : __('Créer le lien') }}</button>
                    </div>
                </section>

                <div class="grid gap-8 lg:grid-cols-2">
                    <section>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="display text-2xl">{{ __('Plan de table') }}</h3>
                            <Link :href="route('guests.index', event.slug)" class="inline-flex min-h-tap items-center text-sm font-semibold text-[var(--accent)] hover:underline">{{ __('Attribuer les tables') }}</Link>
                        </div>
                        <EmptyState v-if="tables.length === 0" class="card" compact icon="table" :title="__('Aucun invité à placer')" :text="__('Les tables apparaîtront quand vous aurez ajouté des invités.')" />
                        <ul v-else class="divide-y card" data-testid="tables">
                            <li v-for="t in tables" :key="t.table" class="flex justify-between p-3 text-sm">
                                <span class="font-medium text-[var(--ink)]">{{ t.table }}</span>
                                <span class="text-[var(--muted)]">{{ __(':guests invité(s) · :seats couvert(s)', { guests: t.guests, seats: t.headcount }) }}</span>
                            </li>
                        </ul>
                    </section>

                    <section>
                        <h3 class="mb-3 display text-2xl">{{ __('Régimes alimentaires') }}</h3>
                        <EmptyState v-if="dietary.length === 0" class="card" compact icon="guests" :title="__('Aucun régime particulier signalé')" :text="__('Les régimes et allergies indiqués par vos invités apparaîtront ici.')" />
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
                        <h3 class="display text-2xl">{{ __('Cagnotte') }}</h3>
                        <Link :href="route('cagnotte.index', event.slug)" class="inline-flex min-h-tap items-center text-sm font-semibold text-[var(--accent)] hover:underline">{{ __('Détail') }}</Link>
                    </div>
                    <p class="mt-1 text-2xl font-semibold text-success">{{ fcfa(cagnotte.raised) }}</p>
                    <div v-if="cagnotte.goal" class="mt-2">
                        <div class="h-2 overflow-hidden rounded-full bg-[var(--line)]"><div class="h-full bg-[var(--accent)]" :style="{ width: percent(cagnotte.raised, cagnotte.goal) + '%' }" /></div>
                        <p class="mt-1 text-xs text-[var(--muted)]">{{ __('objectif :amount (:percent %)', { amount: fcfa(cagnotte.goal), percent: percent(cagnotte.raised, cagnotte.goal) }) }}</p>
                    </div>
                    <p class="mt-2 text-sm text-[var(--muted)]">
                        {{ __(':count en attente', { count: cagnotte.pending }) }}<span v-if="cagnotte.to_review > 0" class="text-danger"> · {{ __(':count à vérifier', { count: cagnotte.to_review }) }}</span>
                    </p>
                </section>

                <section v-if="moderation.entries + moderation.photos > 0" class="notice notice-warning" data-testid="moderation">
                    {{ __(':entries message(s) et :photos photo(s) attendent votre validation.', { entries: moderation.entries, photos: moderation.photos }) }}
                    <Link :href="route('moderation.index', event.slug)" class="inline-flex min-h-tap items-center underline">{{ __('Modérer') }}</Link>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
