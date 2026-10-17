<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormField from '@/Components/ui/FormField.vue';
import { translate as __ } from '@/i18n';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout :title="__('Zone sécurisée')" :subtitle="__('Veuillez confirmer votre mot de passe avant de continuer.')">
        <Head :title="__('Confirmation du mot de passe')" />

        <form class="space-y-5" @submit.prevent="submit">
            <FormField
                id="password"
                v-model="form.password"
                :label="__('Mot de passe')"
                type="password"
                :error="form.errors.password"
                required
                autofocus
                autocomplete="current-password"
            />

            <PrimaryButton class="w-full" :disabled="form.processing">{{ __('Confirmer') }}</PrimaryButton>
        </form>
    </GuestLayout>
</template>
