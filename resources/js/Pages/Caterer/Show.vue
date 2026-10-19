<script setup lang="ts">
import StatCard from '@/Components/ui/StatCard.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    event: { title: string; type_label: string; event_date: string; location_name: string | null };
    expires_at: string;
    counts: { headcount: number; accepted: number; pending: number; declined: number };
    tables: { table: string; headcount: number }[];
    dietary: { guest: string; table: string | null; restriction: string; seats: number }[];
}>();

const date = computed(() => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'full', timeStyle: 'short' }).format(new Date(props.event.event_date)));
const expires = computed(() => new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long', timeStyle: 'short' }).format(new Date(props.expires_at)));
const dietarySeats = computed(() => props.dietary.reduce((sum, d) => sum + d.seats, 0));
const print = () => window.print();
</script>

<template>
    <Head :title="`Traiteur - ${event.title}`" />

    <div class="min-h-screen bg-[var(--paper)] text-[var(--ink)]">
        <a href="#contenu" class="skip-link">Aller au contenu</a>
        <main id="contenu" tabindex="-1" class="mx-auto max-w-5xl space-y-8 px-4 py-10 outline-none sm:px-6">
            <header>
                <p class="eyebrow">{{ event.type_label }} · Vue traiteur</p>
                <h1 class="display mt-2 text-4xl sm:text-5xl">{{ event.title }}</h1>
                <p class="mt-2 text-ink-muted"><span class="inline-block first-letter:uppercase">{{ date }}</span><span v-if="event.location_name"> · {{ event.location_name }}</span></p>
                <p class="notice notice-warning mt-4" data-testid="caterer-expiry">Lecture seule. Ce lien expire le {{ expires }}.</p>
            </header>

            <section>
                <h2 class="mb-3 display text-2xl">Couverts</h2>
                <dl class="grid grid-cols-2 gap-3 text-center sm:grid-cols-4" data-testid="caterer-counts">
                    <StatCard label="Couverts attendus" :value="counts.headcount" tone="good" />
                    <StatCard label="Invités présents" :value="counts.accepted" />
                    <StatCard label="En attente de réponse" :value="counts.pending" tone="warn" />
                    <StatCard label="Absents" :value="counts.declined" tone="bad" />
                </dl>
                <p class="caption mt-2 text-ink-muted">Les chiffres se mettent à jour à mesure que les invités répondent.</p>
            </section>

            <div class="grid gap-8 lg:grid-cols-2">
                <section>
                    <h2 class="mb-3 display text-2xl">Répartition par table</h2>
                    <p v-if="tables.length === 0" class="text-ink-muted">Aucune table attribuée.</p>
                    <ul v-else class="card divide-y divide-line" data-testid="caterer-tables">
                        <li v-for="t in tables" :key="t.table" class="flex justify-between p-3 text-sm">
                            <span class="font-medium">{{ t.table }}</span>
                            <span class="text-ink-muted">{{ t.headcount }} couvert(s)</span>
                        </li>
                    </ul>
                </section>

                <section>
                    <h2 class="mb-3 display text-2xl">Régimes et allergies</h2>
                    <p v-if="dietary.length === 0" class="text-ink-muted">Aucun régime particulier signalé.</p>
                    <template v-else>
                        <p class="mb-2 text-sm text-ink-muted" data-testid="caterer-dietary-seats">{{ dietarySeats }} couvert(s) concerné(s)</p>
                        <ul class="card divide-y divide-line" data-testid="caterer-dietary">
                            <li v-for="(d, i) in dietary" :key="i" class="p-3 text-sm">
                                <span class="font-medium">{{ d.guest }}</span>
                                <span v-if="d.table" class="text-ink-muted"> · {{ d.table }}</span>
                                <span v-if="d.seats > 1" class="text-ink-muted"> · {{ d.seats }} couverts</span>
                                <p class="text-ink-muted">{{ d.restriction }}</p>
                            </li>
                        </ul>
                    </template>
                </section>
            </div>

            <p class="print:hidden"><button type="button" class="btn btn-secondary" @click="print">Imprimer cette page</button></p>
        </main>
    </div>
</template>
