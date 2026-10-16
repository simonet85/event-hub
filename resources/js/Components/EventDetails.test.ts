import type { PublicEvent } from '@/types';
import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EventDetails from './EventDetails.vue';

const base = (over: Partial<PublicEvent> = {}): PublicEvent => ({
    title: 'Mariage',
    slug: 'mariage',
    type: 'wedding',
    type_label: 'Mariage',
    event_date: '2027-06-01T15:00:00Z',
    location_name: null,
    location_address: null,
    welcome_message: null,
    theme_config: { primary_color: '#000000', secondary_color: '#ffffff', font_family: 'body' },
    dress_code: null,
    map: null,
    map_url: null,
    schedule: [],
    ...over,
});

describe('EventDetails', () => {
    it("n'affiche aucune section quand il n'y a rien à montrer", () => {
        const w = mount(EventDetails, { props: { event: base() } });
        expect(w.find('[data-testid="schedule"]').exists()).toBe(false);
        expect(w.find('[data-testid="dress-code"]').exists()).toBe(false);
        expect(w.find('[data-testid="map"]').exists()).toBe(false);
    });

    it('affiche le programme dans l\'ordre reçu avec descriptions', () => {
        const w = mount(EventDetails, {
            props: {
                event: base({
                    schedule: [
                        { id: 1, starts_at: '2027-06-01T15:00:00Z', title: 'Cérémonie', description: 'À la mairie' },
                        { id: 2, starts_at: '2027-06-01T19:00:00Z', title: 'Dîner', description: null },
                    ],
                }),
            },
        });
        const items = w.findAll('li');
        expect(items).toHaveLength(2);
        expect(items[0].text()).toContain('Cérémonie');
        expect(items[0].text()).toContain('À la mairie');
        expect(items[1].text()).toContain('Dîner');
    });

    it('affiche le dress code', () => {
        const w = mount(EventDetails, { props: { event: base({ dress_code: 'Tenue de soirée' }) } });
        expect(w.get('[data-testid="dress-code"]').text()).toContain('Tenue de soirée');
    });

    it('affiche la carte intégrée et le lien quand les coordonnées existent', () => {
        const w = mount(EventDetails, {
            props: {
                event: base({
                    map: { latitude: 48.8, longitude: 2.3, embed_url: 'https://www.openstreetmap.org/export/embed.html?bbox=1' },
                    map_url: 'https://www.openstreetmap.org/search?query=x',
                }),
            },
        });
        expect(w.get('iframe').attributes('src')).toContain('openstreetmap.org/export/embed.html');
        expect(w.get('a').attributes('href')).toContain('search?query=x');
        expect(w.get('a').attributes('rel')).toContain('noopener');
    });

    it("n'affiche que le lien sans coordonnées", () => {
        const w = mount(EventDetails, { props: { event: base({ map_url: 'https://www.openstreetmap.org/search?query=x' }) } });
        expect(w.find('iframe').exists()).toBe(false);
        expect(w.find('a').exists()).toBe(true);
    });
});
