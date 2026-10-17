import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import Contributions from './Contributions.vue';

const post = vi.fn();
let flash: Record<string, unknown> = {};

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue');
    return {
        useForm: (data: Record<string, unknown>) => reactive({ ...data, errors: {}, processing: false, post, reset: vi.fn() }),
        usePage: () => ({ props: { flash } }),
    };
});

vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);

const props = (over = {}) => ({
    token: 'tok',
    guestbook: [],
    photos: [],
    mine: { pending_entries: 0, pending_photos: 0 },
    ...over,
});

describe('Contributions', () => {
    beforeEach(() => {
        post.mockClear();
        flash = {};
    });

    it('affiche les messages et photos approuvés', () => {
        const w = mount(Contributions, {
            props: props({
                guestbook: [{ id: 1, author: 'Alice', message: 'Bravo !' }],
                photos: [{ id: 1, author: 'Bob', caption: 'Danse', url: '/photos/1' }],
            }),
        });
        expect(w.get('[data-testid="guestbook"]').text()).toContain('Bravo !');
        expect(w.get('[data-testid="guestbook"]').text()).toContain('Alice');
        expect(w.get('img').attributes('src')).toBe('/photos/1');
    });

    it('affiche un état vide invitant à participer', () => {
        const w = mount(Contributions, { props: props() });
        expect(w.text()).toContain('Soyez le premier');
        expect(w.text()).toContain('Aucune photo');
    });

    it('désactive l\'envoi tant que le message est vide', () => {
        const w = mount(Contributions, { props: props() });
        expect(w.get('[data-testid="guestbook"] button').attributes('disabled')).toBeDefined();
    });

    it('poste le message vers la route du livre d\'or', async () => {
        const w = mount(Contributions, { props: props() });
        await w.get('textarea').setValue('Super soirée');
        await w.get('[data-testid="guestbook"] form').trigger('submit');
        expect(post).toHaveBeenCalledWith('/guestbook.store/tok', expect.anything());
    });

    it('prévient que les contributions en attente seront validées par les hôtes', () => {
        const w = mount(Contributions, { props: props({ mine: { pending_entries: 1, pending_photos: 2 } }) });
        expect(w.find('[data-testid="entry-pending"]').exists()).toBe(true);
        expect(w.find('[data-testid="photo-pending"]').exists()).toBe(true);
    });

    it('limite le sélecteur de fichier aux formats image acceptés', () => {
        const w = mount(Contributions, { props: props() });
        expect(w.get('input[type="file"]').attributes('accept')).toBe('image/jpeg,image/png,image/webp');
    });

    it('recrée le champ fichier après un envoi réussi pour effacer le nom affiché', async () => {
        post.mockImplementationOnce((_url: string, options: { onSuccess?: () => void }) => options.onSuccess?.());
        const w = mount(Contributions, { props: props() });
        const before = w.get('input[type="file"]').element;

        await w.get('[data-testid="gallery"] form').trigger('submit');

        expect(w.get('input[type="file"]').element).not.toBe(before);
    });
});