<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type Guest = {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    max_companions: number;
    invited_at: string | null;
    invitation_url: string;
    whatsapp_url: string | null;
};

const props = defineProps<{ event: { title: string; slug: string }; guests: Guest[] }>();

const flash = computed(() => usePage().props.flash as { sent?: number; imported?: number } | undefined);

const form = useForm({ name: '', email: '', phone: '', max_companions: 0 });
const importForm = useForm<{ file: File | null }>({ file: null });

const add = () => form.post(route('guests.store', props.event.slug), { onSuccess: () => form.reset() });
const importCsv = () =>
    importForm.post(route('guests.import', props.event.slug), { onSuccess: () => importForm.reset() });
const sendAll = () => router.post(route('invitations.send-all', props.event.slug));
const send = (g: Guest) => router.post(route('invitations.send', [props.event.slug, g.id]));
const remove = (g: Guest) => router.delete(route('guests.destroy', [props.event.slug, g.id]));
const pending = computed(() => props.guests.filter((g) => g.email && !g.invited_at).length);
</script>

<template>
    <Head :title="`Invités - ${event.title}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Invités — {{ event.title }}</h2>
                <button
                    :disabled="pending === 0"
                    class="rounded-md bg-gray-800 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white disabled:opacity-40"
                    @click="sendAll"
                >
                    Envoyer les invitations ({{ pending }})
                </button>
            </div>
        </template>

        <div class="space-y-8 py-12">
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <p v-if="flash?.sent !== undefined" class="rounded bg-green-50 p-3 text-green-800">
                    {{ flash.sent }} invitation(s) envoyée(s).
                </p>
                <p v-if="flash?.imported !== undefined" class="rounded bg-green-50 p-3 text-green-800">
                    {{ flash.imported }} invité(s) importé(s).
                </p>

                <form class="grid gap-3 rounded-lg bg-white p-6 shadow-sm sm:grid-cols-5" @submit.prevent="add">
                    <input v-model="form.name" placeholder="Nom" class="rounded-md border-gray-300" />
                    <input v-model="form.email" type="email" placeholder="Email" class="rounded-md border-gray-300" />
                    <input v-model="form.phone" placeholder="Téléphone (+33...)" class="rounded-md border-gray-300" />
                    <input
                        v-model.number="form.max_companions"
                        type="number"
                        min="0"
                        max="10"
                        title="Accompagnants max"
                        class="rounded-md border-gray-300"
                    />
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-white disabled:opacity-50"
                    >
                        Ajouter
                    </button>
                    <div class="text-sm text-red-600 sm:col-span-5">
                        <p v-for="(msg, key) in form.errors" :key="key">{{ msg }}</p>
                    </div>
                </form>

                <form class="flex items-center gap-3 rounded-lg bg-white p-4 shadow-sm" @submit.prevent="importCsv">
                    <input
                        type="file"
                        accept=".csv,text/csv"
                        @change="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                    />
                    <button
                        type="submit"
                        :disabled="!importForm.file || importForm.processing"
                        class="rounded-md border px-3 py-1 text-sm disabled:opacity-40"
                    >
                        Importer un CSV (name, email, phone, max_companions)
                    </button>
                </form>

                <p v-if="guests.length === 0" class="text-gray-600">Aucun invité pour le moment.</p>
                <ul v-else class="divide-y rounded-lg bg-white shadow-sm">
                    <li v-for="g in guests" :key="g.id" class="flex flex-wrap items-center justify-between gap-2 p-4">
                        <div>
                            <p class="font-medium text-gray-900">{{ g.name }}</p>
                            <p class="text-sm text-gray-500">
                                {{ g.email ?? 'pas d\'email' }} · +{{ g.max_companions }} ·
                                {{ g.invited_at ? 'invité' : 'non invité' }}
                            </p>
                        </div>
                        <div class="flex gap-3 text-sm">
                            <a :href="g.invitation_url" target="_blank" class="text-indigo-600">Voir</a>
                            <button v-if="g.email" class="text-indigo-600" @click="send(g)">Envoyer par email</button>
                            <a v-if="g.whatsapp_url" :href="g.whatsapp_url" target="_blank" class="text-green-700">
                                WhatsApp
                            </a>
                            <button class="text-red-600" @click="remove(g)">Supprimer</button>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
