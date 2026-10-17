<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { translate as __ } from '@/i18n';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    status?: string;
}>();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout
        :title="__('Vérifiez votre email')"
        :subtitle="__('Merci pour votre inscription ! Cliquez sur le lien que nous venons de vous envoyer. Pas reçu ? Nous pouvons vous en renvoyer un.')"
    >
        <Head :title="__('Vérification de l\'email')" />

        <div v-if="verificationLinkSent" class="mb-4 rounded-xl bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
            {{ __("Un nouveau lien de vérification vient d'être envoyé à votre adresse email.") }}
        </div>

        <form class="space-y-4" @submit.prevent="submit">
            <PrimaryButton class="w-full" :disabled="form.processing">{{ __("Renvoyer l'email de vérification") }}</PrimaryButton>

            <p class="text-center text-sm">
                <Link :href="route('logout')" method="post" as="button" class="font-semibold text-[var(--accent)] hover:underline">{{ __('Déconnexion') }}</Link>
            </p>
        </form>
    </GuestLayout>
</template>
