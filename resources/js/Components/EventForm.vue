<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import FormField from '@/Components/ui/FormField.vue';
import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import type { Theme } from '@/types';
import { auditTheme, effectiveSurface } from '@/lib/contrast';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

export interface EventFormValues {
    title: string;
    slug: string;
    type: string;
    event_date: string;
    location_name: string | null;
    location_address: string | null;
    welcome_message: string | null;
    theme_config: Theme;
}

const props = defineProps<{
    types: { value: string; label: string; theme: Theme; css_class?: string; dark?: boolean }[];
    /** Présent en modification : le slug est alors figé. */
    event?: EventFormValues;
}>();

const editing = computed(() => props.event !== undefined);

const form = useForm({
    title: props.event?.title ?? '',
    slug: props.event?.slug ?? '',
    type: props.event?.type ?? props.types[0].value,
    event_date: props.event?.event_date ?? '',
    location_name: props.event?.location_name ?? '',
    location_address: props.event?.location_address ?? '',
    welcome_message: props.event?.welcome_message ?? '',
    theme_config: { ...(props.event?.theme_config ?? props.types[0].theme) } as Theme,
});

// En création, changer de type propose le thème par défaut de ce type ; en modification, on garde le thème choisi.
watch(
    () => form.type,
    (value) => {
        if (editing.value) return;
        const t = props.types.find((x) => x.value === value);
        if (t) form.theme_config = { ...t.theme };
    },
);

const previewTitle = computed(() => form.title || 'Titre de votre événement');
const selectedType = computed(() => props.types.find((t) => t.value === form.type) ?? props.types[0]);

// Avertissement d'accessibilité : couleur principale lisible sur le fond réellement affiché (sombre pour la Fête) et texte de bouton lisible.
const surface = computed(() => effectiveSurface(form.theme_config.secondary_color, selectedType.value.theme.secondary_color, selectedType.value.dark));
const audit = computed(() => auditTheme(form.theme_config.primary_color, surface.value));

const submit = () => {
    if (props.event) {
        form.transform(({ slug: _slug, ...data }) => data).patch(route('events.update', props.event.slug));
    } else {
        form.post(route('events.store'));
    }
};
</script>

<template>
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-2 lg:px-8">
        <form class="card space-y-5 p-6 sm:p-8" @submit.prevent="submit">
            <div>
                <label for="type" class="field-label">Type</label>
                <select id="type" v-model="form.type" class="input">
                    <option v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <InputError :message="form.errors.type" class="mt-1.5" />
            </div>

            <FormField id="title" v-model="form.title" label="Titre" :error="form.errors.title" />

            <FormField
                v-if="!editing"
                id="slug"
                v-model="form.slug"
                label="Adresse personnalisée (optionnel)"
                placeholder="mariage-clara-thomas-2026"
                :error="form.errors.slug"
            />
            <div v-else>
                <span class="field-label">Adresse de l'événement</span>
                <p class="input flex items-center bg-[var(--paper-2)] text-[var(--muted)]" data-testid="frozen-slug">/e/{{ form.slug }}</p>
                <p class="mt-1.5 text-xs text-[var(--muted)]">Figée : les liens et QR codes déjà envoyés restent valides.</p>
            </div>

            <FormField id="event_date" v-model="form.event_date" label="Date et heure" type="datetime-local" :error="form.errors.event_date" />
            <FormField id="location_name" v-model="form.location_name as string" label="Lieu" :error="form.errors.location_name" />
            <FormField id="location_address" v-model="form.location_address as string" label="Adresse du lieu" :error="form.errors.location_address" />

            <div>
                <label for="welcome_message" class="field-label">Message d'accueil</label>
                <textarea id="welcome_message" v-model="form.welcome_message as string" rows="3" class="input" />
                <InputError :message="form.errors.welcome_message" class="mt-1.5" />
            </div>

            <div class="flex flex-wrap gap-6">
                <div>
                    <label for="primary_color" class="field-label">Couleur principale</label>
                    <input id="primary_color" v-model="form.theme_config.primary_color" type="color" class="color-input" />
                </div>
                <div>
                    <label for="secondary_color" class="field-label">Fond</label>
                    <input id="secondary_color" v-model="form.theme_config.secondary_color" type="color" class="color-input" />
                </div>
                <div>
                    <label for="font_family" class="field-label">Police</label>
                    <select id="font_family" v-model="form.theme_config.font_family" class="input !w-auto">
                        <option value="script">Manuscrite</option>
                        <option value="serif">Élégante</option>
                        <option value="body">Moderne</option>
                    </select>
                </div>
            </div>

            <p v-for="(problem, i) in audit?.problems ?? []" :key="i" class="notice notice-warning" role="alert" data-testid="contrast-warning">
                {{ problem }}
            </p>

            <button type="submit" :disabled="form.processing" class="btn">
                {{ editing ? 'Enregistrer les modifications' : 'Créer l\'événement' }}
            </button>
        </form>

        <div class="overflow-hidden rounded-2xl shadow-sm" data-testid="theme-preview">
            <EventPublicLayout :theme="form.theme_config" :preset="selectedType.theme" :css-class="selectedType.css_class" :dark="selectedType.dark">
                <p class="eyebrow mt-20">{{ selectedType.label }}</p>
                <h3 class="display-lg mt-2" style="font-family: var(--event-font)">{{ previewTitle }}</h3>
                <span class="btn mt-6 mb-20" aria-hidden="true">Confirmer ma présence</span>
                <p class="caption -mt-14 mb-16 text-ink-muted">Aperçu du thème</p>
            </EventPublicLayout>
        </div>
    </div>
</template>
