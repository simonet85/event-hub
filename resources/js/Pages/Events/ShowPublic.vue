<script setup lang="ts">
import EventDetails from '@/Components/EventDetails.vue';
import EventHero from '@/Components/ui/EventHero.vue';
import Reveal from '@/Components/ui/Reveal.vue';
import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import type { PublicEvent } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{ event: PublicEvent }>();
</script>

<template>
    <Head :title="event.title" />

    <EventPublicLayout :theme="event.theme_config" :preset="event.theme_preset" :css-class="event.css_class" :dark="event.dark_theme">
        <EventHero :event="event" />

        <Reveal class="w-full">
            <section class="card mx-auto w-full max-w-md space-y-5 p-8">
                <div v-if="event.location_name">
                    <h2 class="eyebrow">Lieu</h2>
                    <p class="heading mt-1">{{ event.location_name }}</p>
                    <p v-if="event.location_address" class="caption text-ink-muted">{{ event.location_address }}</p>
                </div>
                <p v-if="event.welcome_message" class="border-t border-line pt-5 italic">{{ event.welcome_message }}</p>
            </section>
        </Reveal>

        <Reveal class="mt-12 w-full"><EventDetails :event="event" /></Reveal>
    </EventPublicLayout>
</template>
