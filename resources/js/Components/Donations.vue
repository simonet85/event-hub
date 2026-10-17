<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface CagnottePayload {
    goal: number | null;
    message: string | null;
    raised: number;
    gifts: { id: number; name: string; description: string | null; target_amount: number; raised: number; remaining: number }[];
    mine: { id: number; amount: number; gift: string | null; status: string; status_label: string; payment_url: string | null }[];
}

const props = defineProps<{ token: string; cagnotte: CagnottePayload }>();

const MIN_AMOUNT = 100;

const form = useForm<{ amount: number | null; gift_id: number | null; message: string }>({ amount: null, gift_id: null, message: '' });

const fcfa = (n: number) => `${new Intl.NumberFormat('fr-FR').format(n)} FCFA`;
const percent = (raised: number, target: number) => (target > 0 ? Math.min(100, Math.round((raised / target) * 100)) : 0);

const returnedStatus = computed(() => (usePage().props.flash as { donation_status?: string } | undefined)?.donation_status);
const openGifts = computed(() => props.cagnotte.gifts.filter((g) => g.remaining > 0));
const selectedGift = computed(() => props.cagnotte.gifts.find((g) => g.id === form.gift_id) ?? null);
const maxAmount = computed(() => selectedGift.value?.remaining ?? 5_000_000);
const valid = computed(() => form.amount !== null && form.amount >= MIN_AMOUNT && form.amount <= maxAmount.value);

const pick = (giftId: number | null) => {
    form.gift_id = giftId;
    const gift = props.cagnotte.gifts.find((g) => g.id === giftId);
    if (gift) form.amount = gift.remaining;
};

const submit = () => form.post(route('donations.store', props.token), { preserveScroll: true });
</script>

<template>
    <section class="w-full max-w-md space-y-4 card p-6 text-left" data-testid="donations">
        <h2 class="text-center text-xl font-semibold">Cagnotte &amp; cadeaux</h2>
        <p v-if="cagnotte.message" class="text-center text-sm italic">{{ cagnotte.message }}</p>

        <div v-if="cagnotte.goal" data-testid="goal">
            <div class="mb-1 flex justify-between text-sm">
                <span>{{ fcfa(cagnotte.raised) }} collectés</span>
                <span>objectif {{ fcfa(cagnotte.goal) }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-black/10">
                <div class="h-full" :style="{ width: percent(cagnotte.raised, cagnotte.goal) + '%', backgroundColor: 'var(--event-primary)' }" />
            </div>
        </div>
        <p v-else-if="cagnotte.raised > 0" class="text-center text-sm">{{ fcfa(cagnotte.raised) }} déjà collectés</p>

        <p v-if="returnedStatus === 'confirmed'" class="rounded bg-green-100 p-2 text-center text-sm text-green-900" data-testid="donation-confirmed">
            Merci ! Votre contribution est confirmée.
        </p>
        <p v-else-if="returnedStatus === 'pending'" class="rounded bg-amber-100 p-2 text-center text-sm text-amber-900" data-testid="donation-pending">
            Votre paiement est en cours de confirmation. Il apparaîtra ci-dessous dès qu'il sera validé.
        </p>
        <p v-else-if="returnedStatus === 'mismatch'" class="rounded bg-amber-100 p-2 text-center text-sm text-amber-900" data-testid="donation-review">
            Votre paiement est en cours de vérification par les hôtes (le montant reçu diffère). Ils reviendront vers vous si besoin.
        </p>
        <p v-else-if="returnedStatus === 'failed'" class="rounded bg-red-100 p-2 text-center text-sm text-red-900">
            Le paiement n'a pas abouti. Vous pouvez réessayer.
        </p>

        <ul v-if="cagnotte.gifts.length" class="space-y-3" data-testid="gifts">
            <li v-for="g in cagnotte.gifts" :key="g.id" class="rounded-lg border p-3">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-medium">{{ g.name }}</p>
                        <p v-if="g.description" class="text-xs opacity-80">{{ g.description }}</p>
                    </div>
                    <p class="whitespace-nowrap text-sm">{{ fcfa(g.target_amount) }}</p>
                </div>
                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-black/10">
                    <div class="h-full" :style="{ width: percent(g.raised, g.target_amount) + '%', backgroundColor: 'var(--event-primary)' }" />
                </div>
                <p class="mt-1 text-xs opacity-70">{{ g.remaining === 0 ? 'Entièrement financé' : `Il reste ${fcfa(g.remaining)}` }}</p>
            </li>
        </ul>

        <form class="space-y-3" @submit.prevent="submit">
            <label class="block">
                <span class="text-sm">Je participe à</span>
                <select :value="form.gift_id ?? ''" class="input" @change="pick(($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null)">
                    <option value="">La cagnotte générale</option>
                    <option v-for="g in openGifts" :key="g.id" :value="g.id">{{ g.name }} (reste {{ fcfa(g.remaining) }})</option>
                </select>
            </label>
            <label class="block">
                <span class="text-sm">Montant (FCFA, minimum {{ MIN_AMOUNT }})</span>
                <input v-model.number="form.amount" type="number" :min="MIN_AMOUNT" :max="maxAmount" step="1" inputmode="numeric" class="input" />
            </label>
            <label class="block">
                <span class="text-sm">Un petit mot (optionnel)</span>
                <input v-model="form.message" type="text" maxlength="500" class="input" />
            </label>
            <p v-for="(msg, key) in form.errors" :key="key" class="text-sm text-red-700">{{ msg }}</p>
            <button
                type="submit"
                :disabled="form.processing || !valid"
                class="w-full btn"
            >
                Payer avec MoneyFusion
            </button>
            <p class="text-center text-xs opacity-70">Paiement sécurisé par MoneyFusion (Mobile Money, carte…).</p>
        </form>

        <div v-if="cagnotte.mine.length" data-testid="my-contributions">
            <h3 class="mb-1 text-sm font-semibold">Mes contributions</h3>
            <ul class="space-y-1 text-sm">
                <li v-for="c in cagnotte.mine" :key="c.id" class="flex justify-between gap-2">
                    <span>{{ fcfa(c.amount) }}<span v-if="c.gift"> · {{ c.gift }}</span></span>
                    <span>
                        {{ c.status_label }}
                        <a v-if="c.payment_url" :href="c.payment_url" class="underline">Reprendre</a>
                    </span>
                </li>
            </ul>
        </div>
    </section>
</template>
