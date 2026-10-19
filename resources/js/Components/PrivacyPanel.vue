<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = withDefaults(defineProps<{ token: string; retentionDays?: number }>(), { retentionDays: 90 });

const confirming = ref(false);
const working = ref(false);

const erase = () => {
    working.value = true;
    router.delete(route('guest-data.erase', props.token), { onFinish: () => (working.value = false) });
};
</script>

<template>
    <section id="donnees" class="card w-full max-w-md scroll-mt-4 space-y-3 p-6 text-left" data-testid="privacy">
        <p class="eyebrow">Vos données</p>
        <p class="text-sm text-ink-muted">
            Vos réponses ne sont visibles que des organisateurs. Elles sont supprimées automatiquement {{ retentionDays }} jours après l'événement.
            Vous pouvez aussi les supprimer dès maintenant : vos coordonnées, votre réponse, vos messages du livre d'or et vos photos seront effacés.
        </p>
        <button type="button" class="btn btn-secondary" data-testid="privacy-open" @click="confirming = true">Supprimer mes données</button>

        <Modal :show="confirming" max-width="md" @close="confirming = false">
            <div class="p-6 sm:p-8" data-testid="privacy-confirm">
                <h2 class="heading">Supprimer vos données ?</h2>
                <p class="mt-2 text-sm text-ink-muted">
                    Cette action est définitive. Votre invitation ne fonctionnera plus et les organisateurs ne verront plus votre nom, votre réponse, vos messages ni vos photos.
                </p>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" class="btn btn-secondary" @click="confirming = false">Annuler</button>
                    <button type="button" class="btn" :disabled="working" data-testid="privacy-confirm-button" @click="erase">Supprimer définitivement</button>
                </div>
            </div>
        </Modal>
    </section>
</template>
