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
    mine: { pending_entries: 0 },
    ...over,
});

describe('Contributions', () => {
    beforeEach(() => {
        post.mockClear();
        flash = {};
    });

    it('affiche les messages approuvés', () => {
        const w = mount(Contributions, {
            props: props({ guestbook: [{ id: 1, author: 'Alice', message: 'Bravo !' }] }),
        });
        expect(w.get('[data-testid="guestbook"]').text()).toContain('Bravo !');
        expect(w.get('[data-testid="guestbook"]').text()).toContain('Alice');
    });

    it('affiche un état vide invitant à participer', () => {
        const w = mount(Contributions, { props: props() });
        expect(w.text()).toContain('Soyez le premier');
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

    it('prévient que les messages en attente seront validés par les hôtes', () => {
        const w = mount(Contributions, { props: props({ mine: { pending_entries: 1 } }) });
        expect(w.find('[data-testid="entry-pending"]').exists()).toBe(true);
    });
});
