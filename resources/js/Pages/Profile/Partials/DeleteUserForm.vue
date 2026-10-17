<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import FormField from '@/Components/ui/FormField.vue';
import { translate as __ } from '@/i18n';
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref<InstanceType<typeof FormField> | null>(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;
    nextTick(() => passwordInput.value?.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value?.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;
    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-5">
        <header>
            <h2 class="display text-2xl">{{ __('Supprimer mon compte') }}</h2>
            <p class="mt-1 text-sm text-[var(--muted)]">
                {{ __('La suppression est définitive : toutes vos données seront effacées. Pensez à exporter ce que vous souhaitez conserver.') }}
            </p>
        </header>

        <DangerButton @click="confirmUserDeletion">{{ __('Supprimer mon compte') }}</DangerButton>

        <Modal :show="confirmingUserDeletion" @close="closeModal">
            <div class="p-6 sm:p-8">
                <h2 class="display text-2xl">{{ __('Supprimer votre compte ?') }}</h2>
                <p class="mt-2 text-sm text-[var(--muted)]">
                    {{ __('Saisissez votre mot de passe pour confirmer la suppression définitive de votre compte.') }}
                </p>

                <div class="mt-6">
                    <FormField
                        id="delete_password"
                        ref="passwordInput"
                        v-model="form.password"
                        :label="__('Mot de passe')"
                        type="password"
                        :error="form.errors.password"
                        @keyup.enter="deleteUser"
                    />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">{{ __('Annuler') }}</SecondaryButton>
                    <DangerButton :disabled="form.processing" @click="deleteUser">{{ __('Supprimer définitivement') }}</DangerButton>
                </div>
            </div>
        </Modal>
    </section>
</template>
