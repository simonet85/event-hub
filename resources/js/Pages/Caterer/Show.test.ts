import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Show from './Show.vue';

// Même technique que Ticket.test.ts : <Head> d'Inertia exige un contexte d'application.
vi.mock('@inertiajs/vue3', async () => {
    const { defineComponent } = await import('vue');

    return { Head: defineComponent({ render: () => null }), usePage: () => ({ props: {} }) };
});

const base = {
    event: { title: 'Awa & Koffi', type_label: 'Mariage', event_date: '2026-12-16T15:00:00+00:00', location_name: 'Mairie du Plateau' },
    expires_at: '2026-12-20T10:00:00+00:00',
    counts: { headcount: 3, accepted: 2, pending: 1, declined: 1 },
    tables: [{ table: 'Table 1', headcount: 2 }],
    dietary: [{ guest: 'Awa K.', table: 'Table 1', restriction: 'Sans gluten', seats: 2 }],
};

const mountPage = (props = base) => mount(Show, { props });

describe('Vue traiteur', () => {
    it('affiche l\'événement, la mention de lecture seule et la date d\'expiration', () => {
        const w = mountPage();

        expect(w.get('h1').text()).toBe('Awa & Koffi');
        expect(w.get('[data-testid="caterer-expiry"]').text()).toMatch(/Lecture seule.*expire le .*2026/);
        expect(w.text()).toContain('Mairie du Plateau');
    });

    it('affiche les couverts, les tables et les régimes avec les couverts concernés', () => {
        const w = mountPage();

        expect(w.get('[data-testid="caterer-counts"]').text()).toContain('3');
        expect(w.get('[data-testid="caterer-tables"]').text()).toContain('Table 1');
        expect(w.get('[data-testid="caterer-dietary"]').text()).toContain('Awa K.');
        expect(w.get('[data-testid="caterer-dietary"]').text()).toContain('Sans gluten');
        expect(w.get('[data-testid="caterer-dietary-seats"]').text()).toContain('2 couvert(s)');
    });

    it('indique l\'absence de tables et de régimes', () => {
        const w = mountPage({ ...base, tables: [], dietary: [] });

        expect(w.find('[data-testid="caterer-tables"]').exists()).toBe(false);
        expect(w.text()).toContain('Aucune table attribuée.');
        expect(w.text()).toContain('Aucun régime particulier signalé.');
    });

    it('est en lecture seule : aucun champ de saisie ni formulaire', () => {
        const w = mountPage();

        expect(w.findAll('input, textarea, select, form')).toHaveLength(0);
    });

    it('a un contenu principal accessible et un lien d\'évitement', () => {
        const w = mountPage();

        expect(w.find('main#contenu').exists()).toBe(true);
        expect(w.find('a.skip-link[href="#contenu"]').exists()).toBe(true);
    });
});
