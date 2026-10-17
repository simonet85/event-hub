<script setup lang="ts">
import EventForm from '@/Components/EventForm.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Theme } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    types: { value: string; label: string; theme: Theme }[];
}>();
</script>

<template>
    <Head title="Nouvel événement" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="display text-3xl sm:text-4xl">Nouvel événement</h2>
        </template>

        <EventForm :types="types" />
    </AuthenticatedLayout>
</template>
