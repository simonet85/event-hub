<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormField from '@/Components/ui/FormField.vue';
import { translate as __ } from '@/i18n';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const passwordInput = ref<InstanceType<typeof FormField> | null>(null);
const currentPasswordInput = ref<InstanceType<typeof FormField> | null>(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="display text-2xl">{{ __('Mot de passe') }}</h2>
            <p class="mt-1 text-sm text-[var(--muted)]">{{ __('Utilisez un mot de passe long et aléatoire pour sécuriser votre compte.') }}</p>
        </header>

        <form class="mt-6 space-y-5" @submit.prevent="updatePassword">
            <FormField
                id="current_password"
                ref="currentPasswordInput"
                v-model="form.current_password"
                :label="__('Mot de passe actuel')"
                type="password"
                :error="form.errors.current_password"
                autocomplete="current-password"
            />
            <FormField
                id="password"
                ref="passwordInput"
                v-model="form.password"
                :label="__('Nouveau mot de passe')"
                type="password"
                :error="form.errors.password"
                autocomplete="new-password"
            />
            <FormField
                id="password_confirmation"
                v-model="form.password_confirmation"
                :label="__('Confirmer le mot de passe')"
                type="password"
                :error="form.errors.password_confirmation"
                autocomplete="new-password"
            />

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">{{ __('Enregistrer') }}</PrimaryButton>
                <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-700">✓ {{ __('Enregistré.') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
