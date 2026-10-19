import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EmptyState, { type EmptyIcon } from './EmptyState.vue';

describe('EmptyState', () => {
    it('affiche le titre et la phrase d\'aide', () => {
        const w = mount(EmptyState, { props: { icon: 'guests', title: 'Aucun invité', text: 'Ajoutez vos invités.' } });

        expect(w.text()).toContain('Aucun invité');
        expect(w.text()).toContain('Ajoutez vos invités.');
    });

    it('l\'illustration est décorative : masquée aux lecteurs d\'écran et hors de la tabulation', () => {
        const svg = mount(EmptyState, { props: { icon: 'photos', title: 'Aucune photo' } }).get('svg');

        expect(svg.attributes('aria-hidden')).toBe('true');
        expect(svg.attributes('focusable')).toBe('false');
    });

    it.each<EmptyIcon>(['guests', 'messages', 'photos', 'gift', 'calendar', 'event', 'table'])('dessine l\'icône « %s »', (icon) => {
        const w = mount(EmptyState, { props: { icon, title: 'Titre' } });

        // Le fond rond est commun ; chaque icône ajoute au moins un tracé.
        expect(w.findAll('svg > *').length).toBeGreaterThan(1);
    });

    it('accepte une action dans le slot, et seulement si elle est fournie', () => {
        const none = mount(EmptyState, { props: { icon: 'event', title: 'Vide' } });
        const withAction = mount(EmptyState, { props: { icon: 'event', title: 'Vide' }, slots: { default: '<a href="/new" class="btn">Créer</a>' } });

        expect(none.find('a').exists()).toBe(false);
        expect(withAction.get('a').text()).toBe('Créer');
    });

    it('a une version compacte pour les petites zones', () => {
        const w = mount(EmptyState, { props: { icon: 'table', title: 'Vide', compact: true } });

        expect(w.get('svg').classes()).toContain('h-12');
    });

    it('n\'utilise que des couleurs du Design System (jetons), pas de couleur codée en dur', () => {
        const html = mount(EmptyState, { props: { icon: 'gift', title: 'Vide' } }).html();

        expect(html).not.toMatch(/#[0-9a-fA-F]{3,6}/);
        expect(html).toContain('var(--surface-300)');
    });
});
