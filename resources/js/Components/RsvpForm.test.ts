import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import RsvpForm from './RsvpForm.vue';

const post = vi.fn();

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue');
    return {
        useForm: (data: Record<string, unknown>) => reactive({ ...data, errors: {}, processing: false, post }),
        usePage: () => ({ props: { flash: {} } }),
    };
});

vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);

const guest = (over = {}) => ({
    max_companions: 2,
    rsvp_status: 'pending' as const,
    companions_count: 0,
    dietary_restrictions: null,
    favorite_song: null,
    rsvp_message: null,
    ...over,
});

describe('RsvpForm', () => {
    beforeEach(() => post.mockClear());

    it('affiche les champs de présence par défaut', () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest() } });
        expect(w.text()).toContain('Serez-vous des nôtres ?');
        expect(w.text()).toContain('Accompagnants (max 2)');
        expect(w.text()).toContain('Allergies');
    });

    it('masque accompagnants, régime et chanson quand on décline', async () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest() } });
        await w.get('input[value="declined"]').setValue();
        expect(w.text()).not.toContain('Accompagnants');
        expect(w.text()).not.toContain('Allergies');
    });

    it('masque le champ accompagnants si aucun n\'est autorisé', () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest({ max_companions: 0 }) } });
        expect(w.text()).not.toContain('Accompagnants');
    });

    it('poste la réponse vers la route RSVP du jeton', async () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest() } });
        await w.get('form').trigger('submit');
        expect(post).toHaveBeenCalledWith('/rsvp.store/tok', expect.anything());
    });

    it('propose de mettre à jour une réponse existante', () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest({ rsvp_status: 'accepted' }) } });
        expect(w.text()).toContain('Mettre à jour ma réponse');
    });
});
