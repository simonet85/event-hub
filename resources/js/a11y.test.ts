import Modal from '@/Components/Modal.vue';
import EventPublicLayout from '@/Layouts/EventPublicLayout.vue';
import { mount } from '@vue/test-utils';
import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';
import { afterEach, describe, expect, it, vi } from 'vitest';

const root = join(process.cwd(), 'resources', 'js');

const vueFiles = (dir: string): string[] =>
    readdirSync(dir).flatMap((name) => {
        const path = join(dir, name);

        return statSync(path).isDirectory() ? vueFiles(path) : path.endsWith('.vue') ? [path] : [];
    });

describe('navigation au clavier : garde-fous statiques', () => {
    it('aucun composant ne supprime l\'anneau de focus (sauf un repère de saut avec tabindex="-1")', () => {
        const offenders = vueFiles(root).flatMap((file) =>
            readFileSync(file, 'utf8')
                .split('\n')
                .filter((line) => /outline-none|outline:\s*none/.test(line) && !line.includes('tabindex="-1"'))
                .map((line) => `${file.replace(root, '')}: ${line.trim()}`),
        );

        expect(offenders).toEqual([]);
    });

    it('chaque disposition et la landing offrent un lien d\'évitement vers un <main id="contenu">', () => {
        const files = ['Layouts/AuthenticatedLayout.vue', 'Layouts/GuestLayout.vue', 'Layouts/EventPublicLayout.vue', 'Pages/Welcome.vue'];

        for (const file of files) {
            const source = readFileSync(join(root, file), 'utf8');
            expect(source, file).toContain('href="#contenu"');
            expect(source, file).toMatch(/<main id="contenu" tabindex="-1"/);
        }
    });

    it('la feuille de style définit un focus visible global et le lien d\'évitement', () => {
        const css = readFileSync(join(root, '..', 'css', 'app.css'), 'utf8');

        expect(css).toMatch(/:focus-visible\s*\{[^}]*outline:\s*2px solid var\(--line-strong\)/);
        expect(css).toMatch(/\.skip-link:focus\s*\{\s*transform:\s*none/);
    });
});

describe('lien d\'évitement', () => {
    it('est le premier élément de la page publique', () => {
        const w = mount(EventPublicLayout, { props: { theme: { primary_color: '#4e6b48', secondary_color: '#fbf6ee', font_family: 'serif' } }, slots: { default: 'contenu' } });

        expect(w.get('a.skip-link').attributes('href')).toBe('#contenu');
        expect(w.element.firstElementChild?.classList.contains('skip-link')).toBe(true);
        expect(w.get('main').attributes('id')).toBe('contenu');
    });
});

describe('Modal : focus piégé et fermeture clavier', () => {
    afterEach(() => vi.restoreAllMocks());

    const stubDialog = () => {
        const showModal = vi.fn();
        const close = vi.fn();
        HTMLDialogElement.prototype.showModal = showModal;
        HTMLDialogElement.prototype.close = close;

        return { showModal, close };
    };

    it('s\'ouvre avec showModal() : le navigateur piège le focus et rend le reste inerte', async () => {
        const { showModal } = stubDialog();
        const w = mount(Modal, { props: { show: false }, slots: { default: '<button>OK</button>' } });

        await w.setProps({ show: true });

        expect(showModal).toHaveBeenCalledOnce();
        expect(w.element.tagName).toBe('DIALOG');
    });

    it('se ferme avec Échap et par clic sur le fond, sauf si closeable est faux', async () => {
        stubDialog();
        const w = mount(Modal, { props: { show: true }, slots: { default: '<button>OK</button>' }, attachTo: document.body });

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        await w.find('.fixed.inset-0.transform').trigger('click');
        expect(w.emitted('close')).toHaveLength(2);

        await w.setProps({ closeable: false });
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
        expect(w.emitted('close')).toHaveLength(2);
        w.unmount();
    });

    it('utilise les jetons du Design System (pas de gris ni de blanc codés en dur)', () => {
        stubDialog();
        const html = mount(Modal, { props: { show: true } }).html();

        expect(html).not.toMatch(/bg-gray|bg-white/);
        expect(html).toContain('card-invitation');
    });
});
