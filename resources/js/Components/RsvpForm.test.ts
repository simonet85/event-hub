import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
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

const confettiShown = () => document.querySelectorAll('canvas[data-confetti]').length > 0;

let getContextSpy: { mockRestore: () => void } | undefined;

describe('RsvpForm : confettis', () => {
    beforeEach(() => {
        post.mockClear();
        getContextSpy = vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue({ scale: vi.fn(), clearRect: vi.fn(), save: vi.fn(), restore: vi.fn(), translate: vi.fn(), rotate: vi.fn(), beginPath: vi.fn(), arc: vi.fn(), fill: vi.fn(), fillRect: vi.fn() } as unknown as CanvasRenderingContext2D);
        vi.stubGlobal('matchMedia', () => ({ matches: false }));
    });
    afterEach(() => {
        document.querySelectorAll('canvas').forEach((c) => c.remove());
        getContextSpy?.mockRestore();
        vi.unstubAllGlobals();
        vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);
    });

    const submitWith = async (status: 'accepted' | 'declined', succeed: boolean) => {
        post.mockImplementationOnce((_url: string, options: { onSuccess?: () => void }) => {
            if (succeed) options.onSuccess?.();
        });
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest() } });
        await w.get(`input[value="${status}"]`).setValue();
        await w.get('form').trigger('submit');

        return w;
    };

    it('lance des confettis quand la présence est confirmée et enregistrée', async () => {
        await submitWith('accepted', true);
        expect(confettiShown()).toBe(true);
    });

    it('n\'en lance pas quand l\'invité décline', async () => {
        await submitWith('declined', true);
        expect(confettiShown()).toBe(false);
    });

    it('n\'en lance pas tant que la réponse n\'est pas enregistrée (erreur de validation)', async () => {
        await submitWith('accepted', false);
        expect(confettiShown()).toBe(false);
    });

    it('n\'en lance pas si l\'utilisateur a demandé de réduire les animations', async () => {
        vi.stubGlobal('matchMedia', (query: string) => ({ matches: query.includes('reduce') }));
        await submitWith('accepted', true);
        expect(confettiShown()).toBe(false);
    });

    it('arrête les confettis quand le formulaire est retiré de la page', async () => {
        const w = await submitWith('accepted', true);
        expect(confettiShown()).toBe(true);
        w.unmount();
        expect(confettiShown()).toBe(false);
    });
});

describe('RsvpForm : consentement et conservation', () => {
    beforeEach(() => post.mockClear());

    it("ne demande pas de consentement tant qu'aucun régime alimentaire n'est saisi", () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest() } });

        expect(w.find('[data-testid="consent"]').exists()).toBe(false);
    });

    it("demande un consentement explicite dès qu'un régime est saisi, et le poste", async () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest() } });
        await w.get('input[type="text"]').setValue('Sans gluten');

        const box = w.get('[data-testid="consent"] input[type="checkbox"]');
        expect((box.element as HTMLInputElement).checked).toBe(false);
        expect(w.get('[data-testid="consent"]').text()).toContain('santé');

        await box.setValue(true);
        await w.get('form').trigger('submit');
        expect(post).toHaveBeenCalledTimes(1);
    });

    it("retire la case si le régime est effacé ou si l'invité décline", async () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest() } });
        await w.get('input[type="text"]').setValue('Sans gluten');
        expect(w.find('[data-testid="consent"]').exists()).toBe(true);

        await w.get('input[type="text"]').setValue('  ');
        expect(w.find('[data-testid="consent"]').exists()).toBe(false);

        await w.get('input[type="text"]').setValue('Sans gluten');
        await w.get('input[value="declined"]').setValue();
        expect(w.find('[data-testid="consent"]').exists()).toBe(false);
    });

    it('annonce la durée de conservation des réponses', () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest(), retentionDays: 45 } });

        expect(w.get('[data-testid="retention-note"]').text()).toContain('45 jours');
    });
});

describe('RsvpForm : accompagnants et remerciement', () => {
    beforeEach(() => post.mockClear());

    it('règle le nombre d accompagnants avec + et −, entre 0 et le maximum', async () => {
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest({ max_companions: 2 }) } });
        const count = () => w.get('[data-testid="companions-count"]').text();
        const minus = w.get('[data-testid="companions-minus"]');
        const plus = w.get('[data-testid="companions-plus"]');

        expect(count()).toBe('0');
        expect(minus.attributes('disabled')).toBeDefined();

        await plus.trigger('click');
        await plus.trigger('click');
        expect(count()).toBe('2');
        expect(plus.attributes('disabled')).toBeDefined();

        await minus.trigger('click');
        expect(count()).toBe('1');
    });

    const submitted = async (status: 'accepted' | 'declined') => {
        vi.spyOn(HTMLCanvasElement.prototype, 'getContext').mockReturnValue(null);
        post.mockImplementationOnce((_url: string, options: { onSuccess?: () => void }) => options.onSuccess?.());
        const w = mount(RsvpForm, { props: { token: 'tok', guest: guest() } });
        await w.get(`input[value="${status}"]`).setValue();
        await w.get('form').trigger('submit');

        return w;
    };

    it('une fois enregistrée, remplace le formulaire par un remerciement qui mène au billet', async () => {
        const w = await submitted('accepted');

        expect(w.find('form').exists()).toBe(false);
        expect(w.get('[data-testid="rsvp-saved"]').text()).toContain('Merci');

        await w.get('[data-testid="rsvp-ticket"]').trigger('click');
        expect(w.emitted('ticket')).toHaveLength(1);
    });

    it('un invité qui décline n a pas de billet vers lequel être renvoyé', async () => {
        const w = await submitted('declined');

        expect(w.get('[data-testid="rsvp-saved"]').text()).toContain('Vous nous manquerez');
        expect(w.find('[data-testid="rsvp-ticket"]').exists()).toBe(false);
    });

    it('« Modifier ma réponse » rouvre le formulaire', async () => {
        const w = await submitted('accepted');

        await w.get('[data-testid="rsvp-edit"]').trigger('click');

        expect(w.find('form').exists()).toBe(true);
    });
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
