import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PrivacyPanel from './PrivacyPanel.vue';

const post = vi.fn();

vi.mock('@inertiajs/vue3', () => ({ router: { post: (...args: unknown[]) => post(...args) } }));

vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);

beforeEach(() => post.mockClear());

describe('PrivacyPanel', () => {
    it('explique ce qui est conservé, pendant combien de temps, et que l\'hôte traite la demande', () => {
        const w = mount(PrivacyPanel, { props: { token: 'tok', retentionDays: 60 } });

        expect(w.text()).toContain('60 jours après l\'événement');
        expect(w.text()).toContain('demande aux organisateurs');
    });

    it('envoie la demande de suppression à l\'hôte, sans rien supprimer lui-même', async () => {
        const w = mount(PrivacyPanel, { props: { token: 'tok' } });

        await w.get('[data-testid="privacy-request"]').trigger('click');

        expect(post).toHaveBeenCalledTimes(1);
        expect(post.mock.calls[0][0]).toBe('/guest-data.request/tok');
    });

    it('une fois la demande envoyée, confirme et ne propose plus de la refaire', () => {
        const w = mount(PrivacyPanel, { props: { token: 'tok', requested: true } });

        expect(w.get('[data-testid="privacy-requested"]').text()).toContain('transmise aux organisateurs');
        expect(w.find('[data-testid="privacy-request"]').exists()).toBe(false);
    });
});
