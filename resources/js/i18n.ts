import { usePage } from '@inertiajs/vue3';
import type { App } from 'vue';

/**
 * Traduction minimale : le français est la langue source, la clé est donc le texte français.
 * Les autres langues fournissent lang/{locale}.json, partagé par Inertia (props `translations`).
 */
export function translate(key: string, replacements: Record<string, string | number> = {}): string {
    let dictionary: Record<string, string> | undefined;
    try {
        dictionary = usePage().props.translations as Record<string, string> | undefined;
    } catch {
        dictionary = undefined; // hors d'une page Inertia (tests, billet hors ligne) : le texte français sert de repli
    }

    return Object.entries(replacements).reduce(
        (text, [name, value]) => text.replaceAll(`:${name}`, String(value)),
        dictionary?.[key] ?? key,
    );
}

export default {
    install(app: App) {
        app.config.globalProperties.__ = translate;
    },
};
