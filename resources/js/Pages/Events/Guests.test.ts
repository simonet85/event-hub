import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import Guests from './Guests.vue';

let flash: Record<string, unknown> = {};

vi.mock('@inertiajs/vue3', async () => {
    const { reactive, defineComponent: dc } = await import('vue');
    return {
        Head: dc({ render: () => null }),
        Link: dc({ render: () => null }),
        router: { post: vi.fn(), patch: vi.fn(), delete: vi.fn() },
        useForm: (data: Record<string, unknown>) => reactive({ ...data, errors: {}, processing: false, post: vi.fn(), reset: vi.fn() }),
        usePage: () => ({ props: { flash } }),
    };
});

vi.stubGlobal('route', (name: string) => `/${name}`);

const Layout = defineComponent({ template: '<div><slot name="header" /><slot /></div>' });

const mountPage = () =>
    mount(Guests, {
        props: {
            event: { title: 'Mariage', slug: 'mariage' },
            summary: { total: 0, to_invite: 0, pending: 0, accepted: 0, declined: 0, headcount: 0 },
            guests: { data: [], links: [] },
        },
        global: { stubs: { AuthenticatedLayout: Layout } },
    });

describe('Guests page', () => {
    beforeEach(() => {
        flash = {};
    });

    it('n\'affiche aucun bandeau quand les messages partagés valent null', () => {
        flash = { sent: null, imported: null };
        const w = mountPage();
        expect(w.text()).not.toContain('mise(s) en file');
        expect(w.text()).not.toContain('importé(s)');
    });

    it('affiche les bandeaux, y compris pour 0, quand les messages sont présents', () => {
        flash = { sent: 3, imported: 0 };
        const w = mountPage();
        expect(w.text()).toContain('3 invitation(s) mise(s) en file');
        expect(w.text()).toContain('0 invité(s) importé(s)');
    });
});