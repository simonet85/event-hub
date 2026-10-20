<script setup lang="ts">
import { formatTime, intlLocale } from '@/lib/locale';
import type { PublicEvent } from '@/types';

defineProps<{ event: PublicEvent }>();

const time = (iso: string) => new Intl.DateTimeFormat(intlLocale(), { hour: '2-digit', minute: '2-digit' }).format(new Date(iso));
</script>

<template>
    <div class="mx-auto w-full max-w-md space-y-6 text-left">
        <section v-if="event.schedule.length" id="programme" class="card scroll-mt-4 p-6" data-testid="schedule">
            <p class="eyebrow text-center">{{ __('Programme') }}</p>
            <h2 class="display-lg mb-4 text-center">{{ __('La') }} <em>{{ __('journée') }}</em></h2>
            <ol class="space-y-3 border-l-2 pl-4" style="border-color: var(--event-primary)">
                <li v-for="item in event.schedule" :key="item.id">
                    <p class="font-semibold">
                        <span class="tabular-nums">{{ time(item.starts_at) }}</span> — {{ item.title }}
                    </p>
                    <p v-if="item.description" class="text-sm opacity-80">{{ item.description }}</p>
                </li>
            </ol>
        </section>

        <section v-if="event.dress_code" class="card p-6 text-center" data-testid="dress-code">
            <h2 class="eyebrow mb-1">{{ __('Dress code') }}</h2>
            <p class="text-lg">{{ event.dress_code }}</p>
        </section>

        <section v-if="event.map || event.map_url" class="card p-4" data-testid="map">
            <iframe
                v-if="event.map"
                :src="event.map.embed_url"
                :title="__('Carte du lieu')"
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
                {{ __('Ouvrir dans OpenStreetMap') }}
            </a>
        </section>
    </div>
</template>
