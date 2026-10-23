import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import { neighbour, swipeDirection } from '@/lib/swipe';
import PhotoViewer from './PhotoViewer.vue';

const photos = [
    { id: 1, url: '/photos/1', author: 'Alice', caption: 'Danse' },
    { id: 2, url: '/photos/2', author: 'Bob', caption: null },
    { id: 3, url: '/photos/3', author: 'Chloé', caption: 'Gâteau' },
];

let showModal: ReturnType<typeof vi.fn>;
let close: ReturnType<typeof vi.fn>;

beforeEach(() => {
    showModal = vi.fn(function (this: HTMLDialogElement) {
        this.setAttribute('open', '');
    });
    close = vi.fn(function (this: HTMLDialogElement) {
        this.removeAttribute('open');
    });
    HTMLDialogElement.prototype.showModal = showModal as unknown as () => void;
    HTMLDialogElement.prototype.close = close as unknown as () => void;
});

const mountViewer = async (index: number | null = 0) => {
    const w = mount(PhotoViewer, { props: { photos, index }, attachTo: document.body });
    await nextTick();
    await nextTick();

    return w;
};

const lastIndex = (w: ReturnType<typeof mount>) => (w.emitted('update:index') ?? []).at(-1)?.[0];

describe('swipe', () => {
    it('reconnaît un glissement horizontal volontaire et ignore un défilement vertical', () => {
        expect(swipeDirection(200, 100, 100, 110)).toBe('left');
        expect(swipeDirection(100, 100, 200, 90)).toBe('right');
        expect(swipeDirection(100, 100, 130, 100)).toBeNull();
        expect(swipeDirection(100, 100, 190, 200)).toBeNull();
        expect(swipeDirection(100, 100, 110, 260)).toBe('down');
    });

    it('borne les index voisins', () => {
        expect(neighbour(0, -1, 3)).toBe(0);
        expect(neighbour(2, 1, 3)).toBe(2);
        expect(neighbour(1, 1, 3)).toBe(2);
    });
});

describe('PhotoViewer', () => {
    it('reste fermé sans index, et s\'ouvre en fenêtre modale native (focus piégé, reste de la page inerte)', async () => {
        const closed = await mountViewer(null);
        expect(showModal).not.toHaveBeenCalled();
        expect(closed.find('[data-testid="viewer-image"]').exists()).toBe(false);

        const open = await mountViewer(1);
        expect(showModal).toHaveBeenCalledOnce();
        expect(open.get('[data-testid="viewer-image"]').attributes('src')).toBe('/photos/2');
        expect(open.get('[data-testid="viewer-counter"]').text()).toBe('2 / 3');
        expect(open.get('dialog').attributes('aria-label')).toBe('Visionneuse de photos');
    });

    it('met le focus initial sur le bouton Fermer et affiche légende et auteur', async () => {
        const w = await mountViewer(0);
        expect(w.get('[data-testid="viewer-close"]').attributes('autofocus')).toBeDefined();
        expect(w.get('[data-testid="viewer-caption"]').text()).toBe('Danse — Alice');
        expect((await mountViewer(1)).get('[data-testid="viewer-caption"]').text()).toBe('Bob');
    });

    it('sans auteur ni légende (photo de l\'hôte), n\'affiche pas de crédit mais propose le téléchargement', async () => {
        const w = mount(PhotoViewer, { props: { photos: [{ id: 9, url: '/full/9', download: '/dl/9' }], index: 0 }, attachTo: document.body });
        await nextTick();
        await nextTick();

        expect(w.find('[data-testid="viewer-caption"]').exists()).toBe(false);
        expect(w.get('[data-testid="viewer-download"]').attributes('href')).toBe('/dl/9');
        expect(w.get('[data-testid="viewer-image"]').attributes('alt')).toBe("Photo de l'événement");
        w.unmount();
    });

    it('navigue avec les flèches, Début et Fin', async () => {
        const w = await mountViewer(1);
        const dialog = w.get('dialog');

        await dialog.trigger('keydown', { key: 'ArrowRight' });
        expect(lastIndex(w)).toBe(2);
        await dialog.trigger('keydown', { key: 'ArrowLeft' });
        expect(lastIndex(w)).toBe(0);
        await dialog.trigger('keydown', { key: 'End' });
        expect(lastIndex(w)).toBe(2);
        await dialog.trigger('keydown', { key: 'Home' });
        expect(lastIndex(w)).toBe(0);
    });

    it('ne dépasse pas les extrémités et désactive les boutons correspondants', async () => {
        const first = await mountViewer(0);
        expect(first.get('[data-testid="viewer-prev"]').attributes('disabled')).toBeDefined();
        await first.get('dialog').trigger('keydown', { key: 'ArrowLeft' });
        expect(lastIndex(first)).toBe(0);

        const last = await mountViewer(2);
        expect(last.get('[data-testid="viewer-next"]').attributes('disabled')).toBeDefined();
        await last.get('dialog').trigger('keydown', { key: 'ArrowRight' });
        expect(lastIndex(last)).toBe(2);
    });

    it('se ferme avec Échap (événement cancel), le bouton Fermer et un clic sur le fond', async () => {
        const w = await mountViewer(0);

        await w.get('dialog').trigger('cancel');
        expect(lastIndex(w)).toBeNull();

        await w.get('[data-testid="viewer-close"]').trigger('click');
        expect(w.emitted('update:index')?.filter(([v]) => v === null)).toHaveLength(2);

        await w.get('dialog > div').trigger('click');
        expect(w.emitted('update:index')?.filter(([v]) => v === null)).toHaveLength(3);
    });

    it('change de photo par un glissement tactile et ferme par un glissement vers le bas', async () => {
        const w = await mountViewer(1);
        const area = w.get('[data-testid="viewer-image"]').element.parentElement as HTMLElement;
        const pointer = (type: string, x: number, y: number) => {
            const e = new Event(type, { bubbles: true }) as PointerEvent;
            Object.assign(e, { pointerType: 'touch', clientX: x, clientY: y });
            area.dispatchEvent(e);
        };

        pointer('pointerdown', 250, 200);
        pointer('pointerup', 120, 205);
        expect(lastIndex(w)).toBe(2);

        pointer('pointerdown', 120, 200);
        pointer('pointerup', 260, 195);
        expect(lastIndex(w)).toBe(0);

        pointer('pointerdown', 200, 100);
        pointer('pointerup', 205, 320);
        expect(lastIndex(w)).toBeNull();
    });

    it('ignore les glissements à la souris (la souris a les boutons)', async () => {
        const w = await mountViewer(1);
        const area = w.get('[data-testid="viewer-image"]').element.parentElement as HTMLElement;
        for (const [type, x] of [['pointerdown', 250], ['pointerup', 100]] as const) {
            const e = new Event(type, { bubbles: true }) as PointerEvent;
            Object.assign(e, { pointerType: 'mouse', clientX: x, clientY: 0 });
            area.dispatchEvent(e);
        }

        expect(w.emitted('update:index')).toBeUndefined();
    });

    it('referme la fenêtre native quand l\'index repasse à null', async () => {
        const w = await mountViewer(0);
        await w.setProps({ index: null });
        await nextTick();

        expect(close).toHaveBeenCalled();
    });
});
