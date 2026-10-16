<script setup lang="ts">
import type { PublicEvent } from '@/types';

defineProps<{ event: PublicEvent }>();

const time = (iso: string) => new Intl.DateTimeFormat('fr-FR', { hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
</script>

<template>
    <div class="w-full max-w-md space-y-6 text-left">
        <section v-if="event.schedule.length" class="rounded-2xl bg-white/70 p-6 shadow-sm" data-testid="schedule">
            <h2 class="mb-4 text-center text-xl font-semibold">Programme</h2>
            <ol class="space-y-3 border-l-2 pl-4" style="border-color: var(--event-primary)">
                <li v-for="item in event.schedule" :key="item.id">
                    <p class="font-semibold">
                        <span class="tabular-nums">{{ time(item.starts_at) }}</span> — {{ item.title }}
                    </p>
                    <p v-if="item.description" class="text-sm opacity-80">{{ item.description }}</p>
                </li>
            </ol>
        </section>

        <section v-if="event.dress_code" class="rounded-2xl bg-white/70 p-6 text-center shadow-sm" data-testid="dress-code">
            <h2 class="mb-1 text-xs uppercase tracking-widest opacity-70">Dress code</h2>
            <p class="text-lg">{{ event.dress_code }}</p>
        </section>

        <section v-if="event.map || event.map_url" class="rounded-2xl bg-white/70 p-4 shadow-sm" data-testid="map">
            <iframe
                v-if="event.map"
                :src="event.map.embed_url"
                title="Carte du lieu"
                class="h-56 w-full rounded-lg border-0"
                loading="lazy"
            />
            <a
                v-if="event.map_url"
                :href="event.map_url"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-2 block text-center text-sm underline"
            >
                Ouvrir dans OpenStreetMap
            </a>
        </section>
    </div>
</template>
