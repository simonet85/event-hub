<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import FormField from '@/Components/ui/FormField.vue';
import { translate as __ } from '@/i18n';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

const form = useForm({
    name: user.name,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="display text-2xl">{{ __('Informations personnelles') }}</h2>
            <p class="mt-1 text-sm text-[var(--muted)]">{{ __('Mettez à jour votre nom et votre adresse email.') }}</p>
        </header>

        <form class="mt-6 space-y-5" @submit.prevent="form.patch(route('profile.update'))">
            <FormField id="name" v-model="form.name" :label="__('Nom')" :error="form.errors.name" required autofocus autocomplete="name" />
            <FormField id="email" v-model="form.email" :label="__('Adresse email')" type="email" :error="form.errors.email" required autocomplete="username" />

            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-xl bg-amber-50 px-4 py-3 text-sm">
                {{ __("Votre adresse email n'est pas vérifiée.") }}
                <Link :href="route('verification.send')" method="post" as="button" class="font-semibold text-[var(--accent)] underline">
                    {{ __("Renvoyer l'email de vérification") }}
                </Link>
                <p v-show="status === 'verification-link-sent'" class="mt-2 font-medium text-green-700">
                    {{ __("Un nouveau lien de vérification vient d'être envoyé à votre adresse email.") }}
                </p>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">{{ __('Enregistrer') }}</PrimaryButton>
                <Transition enter-active-class="transition" enter-from-class="opacity-0" leave-active-class="transition" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-green-700">✓ {{ __('Enregistré.') }}</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
