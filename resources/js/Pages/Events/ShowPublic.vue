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

    <EventPublicLayout :theme="event.theme_config">
        <EventHero :event="event" />

        <Reveal class="w-full">
            <section class="card mx-auto w-full max-w-md space-y-5 p-8">
                <div v-if="event.location_name">
                    <h2 class="eyebrow">Lieu</h2>
                    <p class="display mt-1 text-2xl">{{ event.location_name }}</p>
                    <p v-if="event.location_address" class="text-sm opacity-80">{{ event.location_address }}</p>
                </div>
                <p v-if="event.welcome_message" class="border-t border-[var(--line)] pt-5 italic">{{ event.welcome_message }}</p>
            </section>
        </Reveal>

        <Reveal class="mt-8 w-full"><EventDetails :event="event" /></Reveal>
    </EventPublicLayout>
</template>
