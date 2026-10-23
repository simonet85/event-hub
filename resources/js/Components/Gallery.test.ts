import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import Gallery from './Gallery.vue';

vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);

const photos = [1, 2, 3].map((id) => ({ id, thumb: `/mini/${id}`, url: `/full/${id}`, download: `/dl/${id}` }));
const mountGallery = (list = photos) => mount(Gallery, { props: { token: 'tok', photos: list }, attachTo: document.body });

describe('Gallery', () => {
    it('affiche un état vide tant que l\'hôte n\'a rien publié', () => {
        const w = mountGallery([]);
        expect(w.text()).toContain('Aucune photo pour le moment');
        expect(w.find('[data-testid="gallery-download-all"]').exists()).toBe(false);
    });

    it('affiche les miniatures et propose de tout télécharger', () => {
        const w = mountGallery();
        expect(w.findAll('img').map((i) => i.attributes('src'))).toEqual(['/mini/1', '/mini/2', '/mini/3']);
        expect(w.get('[data-testid="gallery-count"]').text()).toContain('3');
        expect(w.get('[data-testid="gallery-download-all"]').attributes('href')).toBe('/guest-gallery.archive/tok');
    });

    it('ouvre la visionneuse sur la photo choisie, avec son lien de téléchargement', async () => {
        HTMLDialogElement.prototype.showModal = function (this: HTMLDialogElement) {
            this.setAttribute('open', '');
        };
        const w = mountGallery();

        await w.findAll('[data-testid="photo-open"]')[1].trigger('click');
        await w.vm.$nextTick();

        expect(w.get('[data-testid="viewer-image"]').attributes('src')).toBe('/full/2');
        expect(w.get('[data-testid="viewer-download"]').attributes('href')).toBe('/dl/2');
        w.unmount();
    });

    it('télécharge une sélection : les clics cochent au lieu d\'ouvrir la visionneuse', async () => {
        const w = mountGallery();
        await w.get('[data-testid="gallery-select"]').trigger('click');
        expect(w.find('[data-testid="gallery-download-selection"]').exists()).toBe(false);

        const tiles = w.findAll('[data-testid="photo-open"]');
        await tiles[0].trigger('click');
        await tiles[2].trigger('click');

        expect(w.find('[data-testid="viewer-image"]').exists()).toBe(false);
        expect(tiles[0].attributes('aria-pressed')).toBe('true');
        expect(w.get('[data-testid="gallery-download-selection"]').attributes('href')).toBe('/guest-gallery.archive/tok?ids[]=1&ids[]=3');

        // Un second clic décoche.
        await tiles[0].trigger('click');
        expect(w.get('[data-testid="gallery-download-selection"]').attributes('href')).toBe('/guest-gallery.archive/tok?ids[]=3');
        w.unmount();
    });

    it('plafonne la sélection à 100 photos, comme le serveur', async () => {
        const many = Array.from({ length: 120 }, (_, i) => ({ id: i + 1, thumb: `/m/${i}`, url: `/f/${i}`, download: `/d/${i}` }));
        const w = mountGallery(many);
        await w.get('[data-testid="gallery-select"]').trigger('click');

        for (const tile of w.findAll('[data-testid="photo-open"]')) await tile.trigger('click');

        expect(w.get('[data-testid="gallery-download-selection"]').text()).toContain('100');
        w.unmount();
    });
});
