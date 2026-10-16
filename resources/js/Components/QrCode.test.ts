import { flushPromises, mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import QrCode from './QrCode.vue';

vi.mock('qrcode', () => ({
    default: { toDataURL: vi.fn(async (value: string) => `data:image/png;base64,${btoa(value)}`) },
}));

describe('QrCode', () => {
    it('génère une image à partir de la valeur', async () => {
        const w = mount(QrCode, { props: { value: 'https://exemple.test/i/abc', size: 100 } });
        await flushPromises();

        const img = w.get('img');
        expect(img.attributes('src')).toBe(`data:image/png;base64,${btoa('https://exemple.test/i/abc')}`);
        expect(img.attributes('width')).toBe('100');
    });

    it('se régénère quand la valeur change', async () => {
        const w = mount(QrCode, { props: { value: 'a' } });
        await flushPromises();
        await w.setProps({ value: 'b' });
        await flushPromises();

        expect(w.get('img').attributes('src')).toContain(btoa('b'));
    });
});
