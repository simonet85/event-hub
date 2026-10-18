<script setup lang="ts">
import { translate as __ } from '@/i18n';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';

defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
}>();
</script>

<template>
    <Head :title="__('Profil')" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="display text-3xl sm:text-4xl">{{ __('Mon profil') }}</h2>
        </template>

        <div class="mx-auto max-w-3xl space-y-6 px-4 py-10 sm:px-6">
            <div class="card p-6 sm:p-8">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </div>
            <div class="card p-6 sm:p-8">
                <UpdatePasswordForm />
            </div>
            <div class="card border-danger p-6 sm:p-8">
                <DeleteUserForm />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
