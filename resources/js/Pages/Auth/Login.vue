<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormField from '@/Components/ui/FormField.vue';
import { translate as __ } from '@/i18n';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout :title="__('Bon retour !')" :subtitle="__('Connectez-vous pour gérer vos événements.')">
        <Head :title="__('Connexion')" />

        <div v-if="status" class="notice notice-success mb-4">{{ status }}</div>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField
                id="email"
                v-model="form.email"
                :label="__('Adresse email')"
                type="email"
                :placeholder="__('vous@exemple.com')"
                :error="form.errors.email"
                required
                autofocus
                autocomplete="username"
            />

            <FormField
                id="password"
                v-model="form.password"
                :label="__('Mot de passe')"
                type="password"
                :error="form.errors.password"
                required
                autocomplete="current-password"
            />

            <div class="flex items-center justify-between gap-3">
                <label class="flex cursor-pointer items-center gap-2 text-sm text-[var(--muted)]">
                    <Checkbox v-model:checked="form.remember" name="remember" />
                    {{ __('Se souvenir de moi') }}
                </label>
                <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm font-semibold text-[var(--accent)] hover:underline">
                    {{ __('Mot de passe oublié ?') }}
                </Link>
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">{{ __('Se connecter') }}</PrimaryButton>

            <p class="text-center text-sm text-[var(--muted)]">
                {{ __('Pas encore de compte ?') }}
                <Link :href="route('register')" class="font-semibold text-[var(--accent)] hover:underline">{{ __('Créer un compte') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>
