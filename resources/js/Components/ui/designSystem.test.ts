import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import GiftProgress from './GiftProgress.vue';
import GuestNav from './GuestNav.vue';
import EventHero from './EventHero.vue';
import StatusPill from './StatusPill.vue';


const preset = { primary_color: '#4e6b48', secondary_color: '#fbf6ee', font_family: 'serif' };

describe('StatusPill', () => {
    it.each(['success', 'warning', 'danger'] as const)('affiche toujours un mot et une icône (%s)', (tone) => {
        const w = mount(StatusPill, { props: { tone, label: 'Présent' } });
        expect(w.text()).toBe('Présent');
        expect(w.find('svg').exists()).toBe(true);
        expect(w.classes()).toContain(`pill-${tone}`);
    });
});

describe('GiftProgress', () => {
    it('écrit le montant, le pourcentage et expose une barre accessible', () => {
        const w = mount(GiftProgress, { props: { raised: 620000, goal: 1000000, contributors: 14 } });
        expect(w.get('[data-testid="gift-raised"]').text()).toBe('620 000 F CFA');
        expect(w.get('[data-testid="gift-percent"]').text()).toContain('62 %');
        const bar = w.get('[role="progressbar"]');
        expect(bar.attributes('aria-valuenow')).toBe('62');
        expect(w.get('[data-testid="gift-contributors"]').text()).toContain('14 participants');
    });

    it('plafonne à 100 % et se passe de barre sans objectif', () => {
        expect(mount(GiftProgress, { props: { raised: 2000, goal: 1000 } }).get('[role="progressbar"]').attributes('aria-valuenow')).toBe('100');
        expect(mount(GiftProgress, { props: { raised: 2000 } }).find('[role="progressbar"]').exists()).toBe(false);
    });
});

describe('EventHero (invitation personnelle)', () => {
    const event = {
        title: 'Awa & Koffi',
        slug: 'awa-koffi',
        type: 'wedding',
        type_label: 'Mariage',
        event_date: '2026-12-12T16:00:00Z',
        location_name: 'Hôtel Ivoire',
        location_address: 'Cocody, Abidjan',
        welcome_message: 'Soyez les bienvenus',
        theme_config: preset,
        dress_code: null,
        map: null,
        map_url: null,
        schedule: [],
    };

    it('met le nom de linvité en manuscrit, une seule fois, avec le titre, la date et le lieu', () => {
        const w = mount(EventHero, { props: { event, actions: true, guestName: 'Famille Kouassi', maxCompanions: 2 } });
        expect(w.get('[data-testid="invitation-guest"]').classes()).toContain('script');
        expect(w.text()).toContain('Famille Kouassi');
        expect(w.text().match(/Awa & Koffi/g)).toHaveLength(1);
        expect(w.text().match(/Hôtel Ivoire/g)).toHaveLength(1);
        expect(w.get('[data-testid="invitation-companions"]').text()).toContain('2 accompagnants');
        expect(w.get('[data-testid="invitation-welcome"]').text()).toBe('Soyez les bienvenus');
    });

    it('reste neutre sur la page publique : ni nom d invité ni accompagnants', () => {
        const w = mount(EventHero, { props: { event } });
        expect(w.find('[data-testid="invitation-guest"]').exists()).toBe(false);
        expect(w.find('[data-testid="invitation-companions"]').exists()).toBe(false);
    });
});

describe('GuestNav', () => {
    const tabs = [
        { key: 'invitation' as const, label: 'Invitation' },
        { key: 'rsvp' as const, label: 'RSVP' },
        { key: 'cadeaux' as const, label: 'Cadeaux' },
    ];

    it('affiche un onglet par entrée, avec des cibles de 44 px', () => {
        const w = mount(GuestNav, { props: { tabs, active: 'invitation' } });
        expect(w.findAll('a').map((a) => a.text())).toEqual(['Invitation', 'RSVP', 'Cadeaux']);
        expect(w.get('a').classes()).toContain('min-h-tap');
    });

    it("marque l'onglet actif et émet la sélection sans suivre le lien", async () => {
        const w = mount(GuestNav, { props: { tabs, active: 'rsvp' } });
        const links = w.findAll('a');
        expect(links.map((a) => a.attributes('aria-current'))).toEqual([undefined, 'page', undefined]);
        expect(links[2].attributes('href')).toBe('/?onglet=cadeaux');

        await links[2].trigger('click');
        expect(w.emitted('select')).toEqual([['cadeaux']]);
    });
});

describe('EventPublicLayout', () => {
    const mountLayout = (props: Record<string, unknown>) => mount(EventPublicLayout, { props: { theme: preset, preset, ...props }, slots: { default: 'contenu' } });

    it('garde une colonne centrée, sauf en pleine largeur (page galerie)', () => {
        expect(mountLayout({}).get('main').classes()).toContain('max-w-3xl');

        const wide = mountLayout({ wide: true }).get('main');
        expect(wide.classes()).toContain('max-w-none');
        expect(wide.classes()).not.toContain('max-w-3xl');
    });

    it('applique la classe ev-* du type et le thème clair par défaut', () => {
        const w = mountLayout({ cssClass: 'ev-bapteme' });
        expect(w.classes()).toContain('ev-bapteme');
        expect(w.attributes('data-theme')).toBe('light');
        expect(w.attributes('style') ?? '').not.toContain('--accent:');
    });

    it('un thème identique au préréglage n\'écrase pas les jetons ; une couleur choisie par l\'hôte les écrase', () => {
        expect(mountLayout({}).attributes('style') ?? '').not.toContain('--accent:');

        const custom = mountLayout({ theme: { ...preset, primary_color: '#112233' } });
        expect(custom.attributes('style')).toContain('--accent: #112233');
        expect(custom.attributes('style')).toContain('--on-accent');
    });

    it('passe en mode sombre pour le thème Fête, sauf si l\'hôte choisit son propre fond', () => {
        expect(mountLayout({ cssClass: 'ev-fete', dark: true }).attributes('data-theme')).toBe('dark');

        const custom = mountLayout({ cssClass: 'ev-fete', dark: true, theme: { ...preset, secondary_color: '#ffeedd' } });
        expect(custom.attributes('data-theme')).toBe('light');
        expect(custom.attributes('style')).toContain('--surface-100: #ffeedd');
    });
});
