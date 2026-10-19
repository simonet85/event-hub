import { onBeforeUnmount, ref } from 'vue';

const QUERY = '(prefers-color-scheme: dark)';

/** Nombre de mises en page d'organisateur montées : l'attribut n'est retiré que par la dernière (navigation Inertia). */
let active = 0;

const prefersDark = (): boolean => typeof window !== 'undefined' && window.matchMedia?.(QUERY).matches === true;

/**
 * Mode sombre de l'espace organisateur : suit `prefers-color-scheme` en direct et pose `data-theme="dark"` sur
 * <html> (jetons sombres du Design System, y compris pour le fond de page et les fenêtres modales). Les pages
 * publiques des événements gèrent leur propre thème et ne l'utilisent pas.
 */
export function useHostColorScheme() {
    const scheme = ref<'light' | 'dark'>(prefersDark() ? 'dark' : 'light');
    const root = document.documentElement;
    const media = typeof window !== 'undefined' ? window.matchMedia?.(QUERY) : undefined;

    const apply = () => {
        root.dataset.theme = scheme.value;
        root.style.colorScheme = scheme.value;
    };
    const onChange = (event: { matches: boolean }) => {
        scheme.value = event.matches ? 'dark' : 'light';
        apply();
    };

    active++;
    apply();
    media?.addEventListener?.('change', onChange);

    onBeforeUnmount(() => {
        media?.removeEventListener?.('change', onChange);
        if (--active === 0) {
            delete root.dataset.theme;
            root.style.removeProperty('color-scheme');
        }
    });

    return scheme;
}
