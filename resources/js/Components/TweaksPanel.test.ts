import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import EventForm from './EventForm.vue';
import TweaksPanel from './TweaksPanel.vue';

const wedding = { primary_color: '#4e6b48', secondary_color: '#fbf6ee', font_family: 'serif' };
const baptism = { primary_color: '#42698f', secondary_color: '#fbf6ee', font_family: 'serif' };
const party = { primary_color: '#7a3d6f', secondary_color: '#fbf6ee', font_family: 'serif' };

const presets = [
    { value: 'wedding', label: 'Mariage', theme: wedding },
    { value: 'baptism', label: 'Baptême', theme: baptism },
    { value: 'party', label: 'Fête', theme: party },
    { value: 'other', label: 'Autre', theme: wedding },
];

const mountPanel = (modelValue = wedding, problems: string[] = []) => mount(TweaksPanel, { props: { modelValue, presets, problems } });
const lastEmit = (w: ReturnType<typeof mountPanel>) => (w.emitted('update:modelValue') as unknown[][]).at(-1)?.[0];

describe('TweaksPanel', () => {
    it('propose un préréglage par thème (Mariage et Autre partagent le même)', () => {
        const w = mountPanel();

        expect(w.findAll('[data-testid^="preset-"]').map((b) => b.attributes('data-testid'))).toEqual(['preset-wedding', 'preset-baptism', 'preset-party']);
    });

    it('marque le préréglage actif et en applique un autre en entier', async () => {
        const w = mountPanel();

        expect(w.get('[data-testid="preset-wedding"]').attributes('aria-pressed')).toBe('true');
        expect(w.get('[data-testid="preset-baptism"]').attributes('aria-pressed')).toBe('false');

        await w.get('[data-testid="preset-baptism"]').trigger('click');
        expect(lastEmit(w)).toEqual(baptism);
    });

    it('change seulement l\'accent, en gardant le reste du thème', async () => {
        const w = mountPanel();
        const input = w.get('#primary_color');
        (input.element as HTMLInputElement).value = '#112233';
        await input.trigger('input');

        expect(lastEmit(w)).toEqual({ ...wedding, primary_color: '#112233' });
    });

    it('change seulement le fond', async () => {
        const w = mountPanel();
        const input = w.get('#secondary_color');
        (input.element as HTMLInputElement).value = '#ffffff';
        await input.trigger('input');

        expect(lastEmit(w)).toEqual({ ...wedding, secondary_color: '#ffffff' });
    });

    it('change la police et indique celle qui est choisie', async () => {
        const w = mountPanel();

        expect(w.get('[data-testid="font-serif"]').attributes('aria-pressed')).toBe('true');
        await w.get('[data-testid="font-script"]').trigger('click');
        expect(lastEmit(w)).toEqual({ ...wedding, font_family: 'script' });
    });

    it('un thème modifié n\'a plus de préréglage actif', () => {
        const w = mountPanel({ ...wedding, primary_color: '#112233' });

        expect(w.findAll('[data-testid^="preset-"][aria-pressed="true"]')).toHaveLength(0);
    });

    it('affiche les avertissements de contraste', () => {
        const w = mountPanel(wedding, ['Contraste insuffisant']);

        expect(w.get('[data-testid="contrast-warning"]').text()).toContain('Contraste insuffisant');
    });
});

describe('TweaksPanel dans le formulaire d\'événement', () => {
    const types = [
        { value: 'wedding', label: 'Mariage', theme: wedding, css_class: 'ev-mariage', dark: false },
        { value: 'party', label: 'Fête', theme: party, css_class: 'ev-fete', dark: true },
    ];
    const stubs = { EventPublicLayout: { template: '<div><slot /></div>' } };

    it('un préréglage met à jour l\'aperçu (thème écrit dans theme_config) sans changer le type', async () => {
        const w = mount(EventForm, { props: { types }, global: { stubs } });

        await w.get('[data-testid="preset-party"]').trigger('click');

        expect((w.get('#primary_color').element as HTMLInputElement).value).toBe('#7a3d6f');
        expect((w.get('#type').element as HTMLSelectElement).value).toBe('wedding');
    });

    it('avertit quand l\'accent choisi est illisible sur le fond', async () => {
        const w = mount(EventForm, { props: { types }, global: { stubs } });
        const input = w.get('#primary_color');
        (input.element as HTMLInputElement).value = '#fcf7ee';
        await input.trigger('input');

        expect(w.find('[data-testid="contrast-warning"]').exists()).toBe(true);
    });
});
