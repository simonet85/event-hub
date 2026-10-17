<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormField from '@/Components/ui/FormField.vue';
import { translate as __ } from '@/i18n';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout :title="__('Nouveau mot de passe')" :subtitle="__('Choisissez un mot de passe long et unique.')">
        <Head :title="__('Réinitialisation du mot de passe')" />

        <form class="space-y-5" @submit.prevent="submit">
            <FormField id="email" v-model="form.email" :label="__('Adresse email')" type="email" :error="form.errors.email" required autocomplete="username" />
            <FormField
                id="password"
                v-model="form.password"
                :label="__('Nouveau mot de passe')"
                type="password"
                :error="form.errors.password"
                required
                autofocus
                autocomplete="new-password"
            />
            <FormField
                id="password_confirmation"
                v-model="form.password_confirmation"
                :label="__('Confirmer le mot de passe')"
                type="password"
                :error="form.errors.password_confirmation"
                required
                autocomplete="new-password"
            />

            <PrimaryButton class="w-full" :disabled="form.processing">{{ __('Réinitialiser le mot de passe') }}</PrimaryButton>
        </form>
    </GuestLayout>
</template>
