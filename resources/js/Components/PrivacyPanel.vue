<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

/**
 * Les données de l'invité sont supprimées par l'hôte, responsable du traitement : l'invité lui en fait la demande ici.
 */
const props = withDefaults(defineProps<{ token: string; retentionDays?: number; requested?: boolean }>(), { retentionDays: 90, requested: false });

const working = ref(false);

const request = () => {
    working.value = true;
    router.post(route('guest-data.request', props.token), {}, { preserveScroll: true, onFinish: () => (working.value = false) });
};
</script>

<template>
    <section id="donnees" class="card w-full max-w-md scroll-mt-4 space-y-3 p-6 text-left" data-testid="privacy">
        <p class="eyebrow">{{ __('Vos données') }}</p>
        <p class="text-sm text-ink-muted">
            {{ __('Vos réponses ne sont visibles que des organisateurs. Elles sont supprimées automatiquement :days jours après l\'événement.', { days: retentionDays }) }}
            {{ __('Pour les faire supprimer plus tôt, envoyez une demande aux organisateurs : ils effaceront vos coordonnées, votre réponse et vos messages du livre d\'or.') }}
        </p>

        <p v-if="requested" class="notice notice-success" role="status" data-testid="privacy-requested">
            {{ __('Votre demande a été transmise aux organisateurs. Vos données seront supprimées dès qu\'ils l\'auront traitée.') }}
        </p>
        <button v-else type="button" class="btn btn-secondary" :disabled="working" data-testid="privacy-request" @click="request">
            {{ __('Demander la suppression de mes données') }}
        </button>
    </section>
</template>
