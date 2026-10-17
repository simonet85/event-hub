<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

type Theme = { primary_color: string; secondary_color: string; font_family: string };

const props = defineProps<{
    types: { value: string; label: string; theme: Theme }[];
}>();

const form = useForm({
    title: '',
    slug: '',
    type: props.types[0].value,
    event_date: '',
    location_name: '',
    location_address: '',
    welcome_message: '',
    theme_config: { ...props.types[0].theme } as Theme,
});

watch(
    () => form.type,
    (value) => {
        const t = props.types.find((x) => x.value === value);
        if (t) form.theme_config = { ...t.theme };
    },
);

const previewTitle = computed(() => form.title || 'Titre de votre événement');
const submit = () => form.post(route('events.store'));
</script>

<template>
    <Head title="Nouvel événement" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="display text-3xl sm:text-4xl">Nouvel événement</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                <form class="space-y-4 card p-6" @submit.prevent="submit">
                    <label class="block">
                        <span class="text-sm font-medium text-[var(--ink)]">Type</span>
                        <select v-model="form.type" class="mt-1 block w-full rounded-md border-gray-300">
                            <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                        <span v-if="form.errors.type" class="text-sm text-red-600">{{ form.errors.type }}</span>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-[var(--ink)]">Titre</span>
                        <input v-model="form.title" type="text" class="mt-1 block w-full rounded-md border-gray-300" />
                        <span v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</span>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-[var(--ink)]">Adresse personnalisée (optionnel)</span>
                        <input
                            v-model="form.slug"
                            type="text"
                            placeholder="mariage-clara-thomas-2026"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        />
                        <span v-if="form.errors.slug" class="text-sm text-red-600">{{ form.errors.slug }}</span>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-[var(--ink)]">Date et heure</span>
                        <input
                            v-model="form.event_date"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        />
                        <span v-if="form.errors.event_date" class="text-sm text-red-600">{{
                            form.errors.event_date
                        }}</span>
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-[var(--ink)]">Lieu</span>
                        <input
                            v-model="form.location_name"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        />
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-[var(--ink)]">Adresse du lieu</span>
                        <input
                            v-model="form.location_address"
                            type="text"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        />
                    </label>

                    <label class="block">
                        <span class="text-sm font-medium text-[var(--ink)]">Message d'accueil</span>
                        <textarea
                            v-model="form.welcome_message"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300"
                        />
                    </label>

                    <div class="flex gap-6">
                        <label class="block">
                            <span class="text-sm font-medium text-[var(--ink)]">Couleur principale</span>
                            <input v-model="form.theme_config.primary_color" type="color" class="mt-1 block h-10 w-20" />
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-[var(--ink)]">Fond</span>
                            <input
                                v-model="form.theme_config.secondary_color"
                                type="color"
                                class="mt-1 block h-10 w-20"
                            />
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-[var(--ink)]">Police</span>
                            <select v-model="form.theme_config.font_family" class="mt-1 block rounded-md border-gray-300">
                                <option value="script">Manuscrite</option>
                                <option value="serif">Élégante</option>
                                <option value="body">Moderne</option>
                            </select>
                        </label>
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="btn disabled:opacity-50"
                    >
                        Créer l'événement
                    </button>
                </form>

                <div class="overflow-hidden rounded-lg shadow-sm" data-testid="theme-preview">
                    <EventPublicLayout :theme="form.theme_config">
                        <h3 class="text-4xl" style="font-family: var(--event-font)">{{ previewTitle }}</h3>
                        <p class="mt-2 text-sm opacity-70">Aperçu du thème</p>
                    </EventPublicLayout>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
