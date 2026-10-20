<script setup lang="ts">
import GiftProgress from '@/Components/ui/GiftProgress.vue';
import StatusPill from '@/Components/ui/StatusPill.vue';
import { celebrate, type ConfettiHandle } from '@/lib/confetti';
import { fcfa } from '@/lib/format';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, watch } from 'vue';

export interface CagnottePayload {
    goal: number | null;
    message: string | null;
    raised: number;
    gifts: { id: number; name: string; description: string | null; target_amount: number; raised: number; remaining: number }[];
    mine: { id: number; amount: number; gift: string | null; status: string; status_label: string; payment_url: string | null }[];
}

const props = defineProps<{ token: string; cagnotte: CagnottePayload }>();

const MIN_AMOUNT = 100;
const QUICK_AMOUNTS = [5000, 10000, 25000, 50000];

const form = useForm<{ amount: number | null; gift_id: number | null; message: string }>({ amount: null, gift_id: null, message: '' });

const returnedStatus = computed(() => (usePage().props.flash as { donation_status?: string } | undefined)?.donation_status);
const openGifts = computed(() => props.cagnotte.gifts.filter((g) => g.remaining > 0));
const selectedGift = computed(() => props.cagnotte.gifts.find((g) => g.id === form.gift_id) ?? null);
const maxAmount = computed(() => selectedGift.value?.remaining ?? 5_000_000);
const valid = computed(() => form.amount !== null && form.amount >= MIN_AMOUNT && form.amount <= maxAmount.value);

// Confettis à l'arrivée d'un don confirmé (retour de paiement), jamais pendant le paiement ni pour un statut en attente.
let confetti: ConfettiHandle | null = null;
onMounted(() => {
    if (returnedStatus.value === 'confirmed') confetti = celebrate();
});
watch(returnedStatus, (status, previous) => {
    if (status === 'confirmed' && previous !== 'confirmed') confetti = celebrate();
});
onBeforeUnmount(() => confetti?.cancel());

const tone = (status: string): 'success' | 'warning' | 'danger' =>
    status === 'confirmed' ? 'success' : status === 'failed' || status === 'expired' ? 'danger' : 'warning';

const pick = (giftId: number | null) => {
    form.gift_id = giftId;
    const gift = props.cagnotte.gifts.find((g) => g.id === giftId);
    if (gift) form.amount = gift.remaining;
};

const submit = () => form.post(route('donations.store', props.token), { preserveScroll: true });
</script>

<template>
    <section id="cadeaux" class="card w-full max-w-md scroll-mt-4 space-y-6 p-6 text-left" data-testid="donations">
        <header class="text-center">
            <p class="eyebrow">{{ __('Cadeaux') }}</p>
            <h2 class="display-lg mt-1">{{ __('Cagnotte &') }} <em>{{ __('cadeaux') }}</em></h2>
            <p v-if="cagnotte.message" class="mt-2 italic text-ink-muted">{{ cagnotte.message }}</p>
        </header>

        <div v-if="cagnotte.goal" data-testid="goal">
            <GiftProgress :raised="cagnotte.raised" :goal="cagnotte.goal" :label="__('Déjà collecté')" />
        </div>
        <p v-else-if="cagnotte.raised > 0" class="text-center text-ink-muted">{{ __(':amount déjà collectés', { amount: fcfa(cagnotte.raised) }) }}</p>

        <p v-if="returnedStatus === 'confirmed'" class="notice notice-success" data-testid="donation-confirmed">
            {{ __('Merci ! Votre contribution est confirmée.') }}
        </p>
        <p v-else-if="returnedStatus === 'pending'" class="notice notice-warning" data-testid="donation-pending">
            {{ __('Votre paiement est en cours de confirmation. Il apparaîtra ci-dessous dès qu\'il sera validé.') }}
        </p>
        <p v-else-if="returnedStatus === 'mismatch'" class="notice notice-warning" data-testid="donation-review">
            {{ __('Votre paiement est en cours de vérification par les hôtes (le montant reçu diffère). Ils reviendront vers vous si besoin.') }}
        </p>
        <p v-else-if="returnedStatus === 'failed'" class="notice notice-danger">
            {{ __('Le paiement n\'a pas abouti. Vous pouvez réessayer.') }}
        </p>

        <ul v-if="cagnotte.gifts.length" class="space-y-3" data-testid="gifts">
            <li v-for="g in cagnotte.gifts" :key="g.id" class="border border-line bg-surface-100 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="label">{{ g.name }}</p>
                        <p v-if="g.description" class="caption text-ink-muted">{{ g.description }}</p>
                    </div>
                    <p class="label whitespace-nowrap">{{ fcfa(g.target_amount) }}</p>
                </div>
                <GiftProgress class="mt-3" :raised="g.raised" :goal="g.target_amount" />
                <p class="caption mt-2 text-ink-muted">{{ g.remaining === 0 ? __('Entièrement financé') : __('Il reste :amount', { amount: fcfa(g.remaining) }) }}</p>
            </li>
        </ul>

        <form class="space-y-4" @submit.prevent="submit">
            <label class="block">
                <span class="field-label">{{ __('Je participe à') }}</span>
                <select :value="form.gift_id ?? ''" class="input" @change="pick(($event.target as HTMLSelectElement).value ? Number(($event.target as HTMLSelectElement).value) : null)">
                    <option value="">{{ __('La cagnotte générale') }}</option>
                    <option v-for="g in openGifts" :key="g.id" :value="g.id">{{ __(':name (reste :amount)', { name: g.name, amount: fcfa(g.remaining) }) }}</option>
                </select>
            </label>

            <div>
                <label for="donation-amount" class="field-label">{{ __('Montant (F CFA, minimum :min)', { min: MIN_AMOUNT }) }}</label>
                <input id="donation-amount" v-model.number="form.amount" type="number" :min="MIN_AMOUNT" :max="maxAmount" step="1" inputmode="numeric" class="input" />
                <div class="mt-2 flex flex-wrap gap-2" role="group" :aria-label="__('Montants suggérés')" data-testid="quick-amounts">
                    <button
                        v-for="a in QUICK_AMOUNTS"
                        :key="a"
                        type="button"
                        class="chip"
                        :class="form.amount === a ? '!border-accent !bg-accent !text-on-accent' : ''"
                        :aria-pressed="form.amount === a"
                        @click="form.amount = a"
                    >
                        {{ fcfa(a) }}
                    </button>
                </div>
            </div>

            <label class="block">
                <span class="field-label">{{ __('Un petit mot (optionnel)') }}</span>
                <input v-model="form.message" type="text" maxlength="500" class="input" />
            </label>

            <p v-for="(msg, key) in form.errors" :key="key" class="flex items-start gap-1 text-sm text-danger" role="alert">
                <span aria-hidden="true">✕</span> {{ __('Erreur : :message', { message: String(msg) }) }}
            </p>

            <button type="submit" :disabled="form.processing || !valid" :aria-busy="form.processing" class="btn w-full">
                {{ form.processing ? __('Redirection vers MoneyFusion…') : __('Offrir ma participation') }}
            </button>
            <p class="caption text-center text-ink-muted">{{ __('Paiement sécurisé par MoneyFusion (Mobile Money, carte…).') }}</p>
        </form>

        <div v-if="cagnotte.mine.length" data-testid="my-contributions">
            <h3 class="label mb-2">{{ __('Mes contributions') }}</h3>
            <ul class="space-y-2">
                <li v-for="c in cagnotte.mine" :key="c.id" class="flex flex-wrap items-center justify-between gap-2 text-sm">
                    <span>{{ fcfa(c.amount) }}<span v-if="c.gift"> · {{ c.gift }}</span></span>
                    <span class="flex items-center gap-2">
                        <StatusPill :tone="tone(c.status)" :label="c.status_label" />
                        <a v-if="c.payment_url" :href="c.payment_url" class="btn-quiet btn !min-h-0 !px-0 !py-0">{{ __('Reprendre') }}</a>
                    </span>
                </li>
            </ul>
        </div>
    </section>
</template>
