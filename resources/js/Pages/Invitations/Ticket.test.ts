import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import Ticket from './Ticket.vue';

const offline = vi.hoisted(() => ({
    supported: true,
    saved: false,
    saveOfflineTicket: vi.fn(async () => {}),
    clearOfflineTicket: vi.fn(async () => {}),
}));

vi.mock('@/lib/offline', () => ({
    offlineSupported: () => offline.supported,
    isOfflineTicketSaved: async () => offline.saved,
    saveOfflineTicket: offline.saveOfflineTicket,
    clearOfflineTicket: offline.clearOfflineTicket,
}));
vi.mock('@inertiajs/vue3', async () => {
    const { defineComponent } = await import('vue');

    return { Head: defineComponent({ render: () => null }), usePage: () => ({ props: {} }) };
});
vi.mock('qrcode', () => ({ default: { toDataURL: vi.fn(async () => 'data:image/png;base64,AAAA') } }));

const props = {
    guest: { name: 'Aminata Zéhoré', url: 'https://exemple.test/i/jeton' },
    ticketPath: '/i/jeton/billet',
    manifestUrl: 'https://exemple.test/i/jeton/billet/manifest.webmanifest',
    event: {
        title: 'Awa & Koffi',
        slug: 'awa-koffi',
        type: 'wedding',
        type_label: 'Mariage',
        event_date: '2026-12-12T16:00:00Z',
        location_name: 'Hôtel Ivoire',
        location_address: 'Cocody, Abidjan',
        welcome_message: null,
        theme_config: { primary_color: '#4e6b48', secondary_color: '#fbf6ee', font_family: 'serif' },
        dress_code: null,
        map: null,
        map_url: null,
        schedule: [],
    },
};

const mountTicket = async () => {
    const w = mount(Ticket, { props });
    await flushPromises();

    return w;
};

describe('Billet', () => {
    beforeEach(() => {
        offline.supported = true;
        offline.saved = false;
        offline.saveOfflineTicket.mockClear();
        offline.clearOfflineTicket.mockClear();
        vi.stubGlobal('navigator', { ...navigator, onLine: false });
    });

    it('affiche le nom de l\'invité, l\'événement et le QR code', async () => {
        const w = await mountTicket();

        expect(w.get('[data-testid="ticket-guest"]').text()).toBe('Aminata Zéhoré');
        expect(w.text()).toContain('Awa & Koffi');
        expect(w.text()).toContain('Hôtel Ivoire');
        expect(w.find('img').exists()).toBe(true);
    });

    it('garde le billet sur demande, puis propose de l\'effacer', async () => {
        const w = await mountTicket();
        expect(w.find('[data-testid="offline-saved"]').exists()).toBe(false);

        await w.get('[data-testid="offline-keep"]').trigger('click');
        await flushPromises();

        expect(offline.saveOfflineTicket).toHaveBeenCalledWith('/i/jeton/billet');
        expect(w.get('[data-testid="offline-saved"]').text()).toContain('Disponible hors ligne');
        expect(w.find('[data-testid="offline-keep"]').exists()).toBe(false);

        await w.get('[data-testid="offline-clear"]').trigger('click');
        await flushPromises();

        expect(offline.clearOfflineTicket).toHaveBeenCalledOnce();
        expect(w.find('[data-testid="offline-saved"]').exists()).toBe(false);
        expect(w.find('[data-testid="offline-keep"]').exists()).toBe(true);
    });

    it('explique ce qui est gardé et pourquoi effacer sur un appareil partagé', async () => {
        const w = await mountTicket();
        const panel = w.get('[data-testid="offline-panel"]').text();

        expect(panel).toContain('Seul ce billet est gardé');
        expect(panel).toContain('appareil partagé');
    });

    it('reconnaît un billet déjà gardé au chargement', async () => {
        offline.saved = true;
        const w = await mountTicket();

        expect(w.find('[data-testid="offline-saved"]').exists()).toBe(true);
        expect(w.find('[data-testid="offline-clear"]').exists()).toBe(true);
    });

    it('propose une capture d\'écran quand le navigateur ne gère pas le hors ligne', async () => {
        offline.supported = false;
        const w = await mountTicket();

        expect(w.find('[data-testid="offline-unsupported"]').exists()).toBe(true);
        expect(w.find('[data-testid="offline-keep"]').exists()).toBe(false);
    });

    it('affiche une erreur claire si la copie échoue', async () => {
        offline.saveOfflineTicket.mockRejectedValueOnce(new Error('quota'));
        const w = await mountTicket();

        await w.get('[data-testid="offline-keep"]').trigger('click');
        await flushPromises();

        expect(w.get('[role="alert"]').text()).toContain('Impossible');
    });
});
