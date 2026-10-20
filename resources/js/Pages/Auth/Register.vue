<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormField from '@/Components/ui/FormField.vue';
import { translate as __ } from '@/i18n';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout :title="__('Créer votre compte')" :subtitle="__('Quelques secondes pour lancer votre premier événement.')">
        <Head :title="__('Inscription')" />

        <form class="space-y-5" @submit.prevent="submit">
            <FormField id="name" v-model="form.name" :label="__('Nom')" :error="form.errors.name" required autofocus autocomplete="name" />
            <FormField
                id="email"
                v-model="form.email"
                :label="__('Adresse email')"
                type="email"
                :placeholder="__('vous@exemple.com')"
                :error="form.errors.email"
                required
                autocomplete="username"
            />
            <FormField
                id="password"
                v-model="form.password"
                :label="__('Mot de passe')"
                type="password"
                :hint="__('8 caractères minimum.')"
                :error="form.errors.password"
                required
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

            <PrimaryButton class="w-full" :disabled="form.processing">{{ __('Créer mon compte') }}</PrimaryButton>

            <p class="text-center text-sm text-[var(--muted)]">
                {{ __('Déjà inscrit ?') }}
                <Link :href="route('login')" class="font-semibold text-[var(--accent)] hover:underline">{{ __('Se connecter') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>
