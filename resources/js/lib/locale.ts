import { usePage } from '@inertiajs/vue3';

/** Langue courante de l'interface ('fr' par défaut), partagée par Inertia. */
export function currentLocale(): string {
    try {
        return (usePage().props.locale as string | undefined) ?? 'fr';
    } catch {
        return 'fr'; // hors d'une page Inertia (tests, billet hors ligne)
    }
}

/** Locale pour Intl : dates et nombres suivent la langue choisie. */
export const intlLocale = (): string => (currentLocale() === 'en' ? 'en-GB' : 'fr-FR');

/** Heure courte : « 15 h 00 » en français, « 15:00 » en anglais. */
export function formatTime(date: Date): string {
    const time = new Intl.DateTimeFormat(intlLocale(), { timeStyle: 'short' }).format(date);

    return currentLocale() === 'en' ? time : time.replace(':', ' h ');
}
