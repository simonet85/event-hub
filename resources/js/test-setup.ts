import { config } from '@vue/test-utils';

/**
 * L'application installe `__()` comme propriété globale (resources/js/i18n.ts). Dans les tests, il n'y a pas de page Inertia :
 * `__()` renvoie le texte français (la clé) avec les remplacements `:nom` appliqués, ce que les tests attendent.
 */
config.global.mocks = {
    ...config.global.mocks,
    __: (key: string, replacements: Record<string, string | number> = {}) =>
        Object.entries(replacements).reduce((text, [name, value]) => text.replaceAll(`:${name}`, String(value)), key),
};
