import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PrivacyPanel from './PrivacyPanel.vue';

const del = vi.fn();

vi.mock('@inertiajs/vue3', () => ({ router: { delete: (...args: unknown[]) => del(...args) } }));

vi.stubGlobal('route', (name: string, param: string) => `/${name}/${param}`);

// jsdom ne connaît pas <dialog>.showModal() : on ouvre la fenêtre en posant l'attribut, comme le ferait le navigateur.
beforeEach(() => {
    del.mockClear();
    HTMLDialogElement.prototype.showModal = function showModal() {
        this.setAttribute('open', '');
    };
    HTMLDialogElement.prototype.close = function close() {
        this.removeAttribute('open');
    };
});

describe('PrivacyPanel', () => {
    it('explique ce qui est conservé et pendant combien de temps', () => {
        const w = mount(PrivacyPanel, { props: { token: 'tok', retentionDays: 60 } });

        expect(w.text()).toContain('60 jours après l\'événement');
        expect(w.text()).toContain('organisateurs');
    });

    it('ne supprime rien sans confirmation', async () => {
        const w = mount(PrivacyPanel, { props: { token: 'tok' }, attachTo: document.body });

        await w.get('[data-testid="privacy-open"]').trigger('click');

        expect(del).not.toHaveBeenCalled();
        expect(w.find('[data-testid="privacy-confirm"]').exists()).toBe(true);
        w.unmount();
    });

    it('supprime les données de l\'invité une fois confirmé', async () => {
        const w = mount(PrivacyPanel, { props: { token: 'tok' }, attachTo: document.body });

        await w.get('[data-testid="privacy-open"]').trigger('click');
        await w.get('[data-testid="privacy-confirm-button"]').trigger('click');

        expect(del).toHaveBeenCalledTimes(1);
        expect(del.mock.calls[0][0]).toBe('/guest-data.erase/tok');
        w.unmount();
    });
});
