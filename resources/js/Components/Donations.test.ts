import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import Donations, { type CagnottePayload } from './Donations.vue';

const post = vi.fn();
let flash: Record<string, unknown> = {};

vi.mock('@inertiajs/vue3', async () => {
    const { reactive } = await import('vue');
    return {
        useForm: (data: Record<string, unknown>) => reactive({ ...data, errors: {}, processing: false, post }),
        usePage: () => ({ props: { flash } }),
    };
});

vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);

const cagnotte = (over: Partial<CagnottePayload> = {}): CagnottePayload => ({
    goal: null,
    message: null,
    raised: 0,
    gifts: [],
    mine: [],
    ...over,
});

const gift = { id: 1, name: 'Lune de miel', description: 'Un voyage', target_amount: 50000, raised: 20000, remaining: 30000 };

describe('Donations', () => {
    beforeEach(() => {
        post.mockClear();
        flash = {};
    });

    it('affiche la progression vers l\'objectif', () => {
        const w = mount(Donations, { props: { token: 'tok', cagnotte: cagnotte({ goal: 100000, raised: 25000 }) } });
        expect(w.get('[data-testid="goal"]').text()).toContain('25');
        expect(w.get('[data-testid="goal"] [style]').attributes('style')).toContain('width: 25%');
    });

    it('liste les cadeaux avec le reste à financer', () => {
        const w = mount(Donations, { props: { token: 'tok', cagnotte: cagnotte({ gifts: [gift] }) } });
        expect(w.get('[data-testid="gifts"]').text()).toContain('Lune de miel');
        expect(w.get('[data-testid="gifts"]').text()).toContain('Il reste');
    });

    it('n\'autorise pas l\'envoi sous le montant minimum', async () => {
        const w = mount(Donations, { props: { token: 'tok', cagnotte: cagnotte() } });
        await w.get('input[type="number"]').setValue(50);
        expect(w.get('button[type="submit"]').attributes('disabled')).toBeDefined();
    });

    it('autorise l\'envoi à partir de 100 FCFA et poste vers la route de paiement', async () => {
        const w = mount(Donations, { props: { token: 'tok', cagnotte: cagnotte() } });
        await w.get('input[type="number"]').setValue(100);
        expect(w.get('button[type="submit"]').attributes('disabled')).toBeUndefined();

        await w.get('form').trigger('submit');
        expect(post).toHaveBeenCalledWith('/donations.store/tok', { preserveScroll: true });
    });

    it('limite le montant à ce qu\'il reste pour le cadeau choisi', async () => {
        const w = mount(Donations, { props: { token: 'tok', cagnotte: cagnotte({ gifts: [gift] }) } });
        await w.get('select').setValue('1');

        const input = w.get('input[type="number"]');
        expect((input.element as HTMLInputElement).value).toBe('30000');
        expect(input.attributes('max')).toBe('30000');

        await input.setValue(30001);
        expect(w.get('button[type="submit"]').attributes('disabled')).toBeDefined();
    });

    it('ne propose plus un cadeau entièrement financé', () => {
        const done = { ...gift, id: 2, name: 'Complet', raised: 50000, remaining: 0 };
        const w = mount(Donations, { props: { token: 'tok', cagnotte: cagnotte({ gifts: [gift, done] }) } });
        const options = w.findAll('select option').map((o) => o.text());
        expect(options.some((t) => t.includes('Lune de miel'))).toBe(true);
        expect(options.some((t) => t.includes('Complet'))).toBe(false);
        expect(w.get('[data-testid="gifts"]').text()).toContain('Entièrement financé');
    });

    it('informe du statut au retour du paiement', () => {
        flash = { donation_status: 'pending' };
        const pending = mount(Donations, { props: { token: 'tok', cagnotte: cagnotte() } });
        expect(pending.find('[data-testid="donation-pending"]').exists()).toBe(true);

        flash = { donation_status: 'confirmed' };
        const ok = mount(Donations, { props: { token: 'tok', cagnotte: cagnotte() } });
        expect(ok.find('[data-testid="donation-confirmed"]').exists()).toBe(true);
    });

    it('affiche mes contributions avec un lien pour reprendre un paiement en attente', () => {
        const w = mount(Donations, {
            props: {
                token: 'tok',
                cagnotte: cagnotte({
                    mine: [
                        { id: 1, amount: 5000, gift: null, status: 'pending', status_label: 'En attente', payment_url: 'https://payin.moneyfusion.net/payment/x/M' },
                        { id: 2, amount: 2000, gift: 'Lune de miel', status: 'confirmed', status_label: 'Confirmée', payment_url: null },
                    ],
                }),
            },
        });
        const items = w.findAll('[data-testid="my-contributions"] li');
        expect(items).toHaveLength(2);
        expect(items[0].find('a').attributes('href')).toBe('https://payin.moneyfusion.net/payment/x/M');
        expect(items[1].find('a').exists()).toBe(false);
    });
});
