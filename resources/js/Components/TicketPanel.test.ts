import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import TicketPanel from './TicketPanel.vue';

vi.mock('@inertiajs/vue3', () => ({ router: { post: vi.fn() } }));
vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);

const event = {
    title: 'Awa & Koffi',
    slug: 'awa-koffi',
    type: 'wedding',
    type_label: 'Mariage',
    event_date: '2026-12-12T16:00:00Z',
    location_name: 'Hôtel Ivoire',
    location_address: null,
    welcome_message: null,
    theme_config: { primary_color: '#4e6b48', secondary_color: '#fbf6ee', font_family: 'serif' },
    dress_code: null,
    map: null,
    map_url: null,
    schedule: [],
};

const guest = (over = {}) => ({
    name: 'Famille Kouassi',
    url: 'https://exemple.test/i/abc',
    max_companions: 2,
    rsvp_status: 'accepted' as const,
    companions_count: 1,
    ticket_number: 'A1B2-C3D4',
    erasure_requested: false,
    ...over,
});

const mountPanel = (over = {}) =>
    mount(TicketPanel, {
        props: { token: 'tok', event, retentionDays: 90, guest: guest(over) },
        global: { stubs: { QrCode: { template: '<img data-testid="qr" alt="QR code" />' } } },
    });

describe('TicketPanel', () => {
    it('montre le QR code, le numéro, le nombre de places et de quoi enregistrer ou imprimer', () => {
        const w = mountPanel();

        expect(w.find('[data-testid="qr"]').exists()).toBe(true);
        expect(w.get('[data-testid="ticket-number"]').text()).toBe('N° A1B2-C3D4');
        expect(w.get('[data-testid="ticket-seats"]').text()).toBe('2 place(s)');
        expect(w.get('[data-testid="ticket-guest"]').classes()).toContain('script');
        expect(w.get('[data-testid="ticket-link"]').attributes('href')).toBe('/invitations.ticket/tok');
        expect(w.find('[data-testid="ticket-print"]').exists()).toBe(true);
    });

    it('imprime la page au clic sur Imprimer', async () => {
        const print = vi.spyOn(window, 'print').mockImplementation(() => {});

        await mountPanel().get('[data-testid="ticket-print"]').trigger('click');

        expect(print).toHaveBeenCalledOnce();
    });

    it('sans réponse, annonce le maximum de places', () => {
        const w = mountPanel({ rsvp_status: 'pending', companions_count: 0 });

        expect(w.get('[data-testid="ticket-seats"]').text()).toBe('Jusqu\'à 3 place(s)');
    });

    it('un invité qui a décliné n\'a pas de billet et peut changer sa réponse', async () => {
        const w = mountPanel({ rsvp_status: 'declined' });

        expect(w.find('[data-testid="ticket-card"]').exists()).toBe(false);
        expect(w.find('[data-testid="qr"]').exists()).toBe(false);

        await w.get('[data-testid="ticket-declined"] a').trigger('click');
        expect(w.emitted('rsvp')).toHaveLength(1);
    });

    it('garde la demande de suppression des données sous le billet', () => {
        expect(mountPanel().find('[data-testid="privacy"]').exists()).toBe(true);
    });
});
