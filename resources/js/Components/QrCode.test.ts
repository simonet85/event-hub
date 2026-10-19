import { flushPromises, mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import QRCode from 'qrcode';
import QrCode from './QrCode.vue';

// Espion plutôt que vi.mock : avec isolate:false, le cache de modules est partagé entre fichiers et un vi.mock peut être ignoré.
describe('QrCode', () => {
    let toDataURL: ReturnType<typeof vi.spyOn>;

    beforeEach(() => {
        toDataURL = vi.spyOn(QRCode, 'toDataURL').mockImplementation((async (value: string) => `data:image/png;base64,${btoa(value)}`) as never);
    });
    afterEach(() => toDataURL.mockRestore());

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

    it("fixe explicitement le niveau de correction d'erreur", async () => {
        mount(QrCode, { props: { value: 'x' } });
        await flushPromises();

        expect(toDataURL).toHaveBeenCalledWith('x', expect.objectContaining({ errorCorrectionLevel: 'M' }));
    });
});
