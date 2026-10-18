<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormField from '@/Components/ui/FormField.vue';
import { translate as __ } from '@/i18n';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout
        :title="__('Mot de passe oublié ?')"
        :subtitle="__('Indiquez votre adresse email : nous vous enverrons un lien pour en choisir un nouveau.')"
    >
        <Head :title="__('Mot de passe oublié')" />

        <div v-if="status" class="notice notice-success mb-4">{{ status }}</div>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.email"
                :label="__('Adresse email')"
                type="email"
                placeholder="vous@exemple.com"
                :error="form.errors.email"
                required
                autofocus
                autocomplete="username"
            />

            <PrimaryButton class="w-full" :disabled="form.processing">{{ __('Envoyer le lien') }}</PrimaryButton>

            <p class="text-center text-sm">
                <Link :href="route('login')" class="font-semibold text-[var(--accent)] hover:underline">← {{ __('Retour à la connexion') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>
