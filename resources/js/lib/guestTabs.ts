/** Onglets de la page d'invitation. L'onglet actif vit dans l'URL (`?onglet=`) : retour arrière et liens partageables. */
export const GUEST_TABS = ['invitation', 'rsvp', 'billet', 'cadeaux', 'souvenirs'] as const;
export type GuestTab = (typeof GUEST_TABS)[number];

export const TAB_PARAM = 'onglet';

/** Anciennes ancres (e-mails déjà envoyés, retour de paiement) vers l'onglet qui contient désormais la section. */
const LEGACY_HASH: Record<string, GuestTab> = {
    invitation: 'invitation',
    programme: 'invitation',
    rsvp: 'rsvp',
    donnees: 'billet',
    cadeaux: 'cadeaux',
    'livre-dor': 'souvenirs',
    photos: 'souvenirs',
};

/** Onglet demandé par l'URL (paramètre, sinon ancre héritée), limité aux onglets disponibles. */
export function resolveTab(search: string, hash: string, available: readonly GuestTab[]): GuestTab {
    const fromQuery = new URLSearchParams(search).get(TAB_PARAM) as GuestTab | null;
    const fromHash = LEGACY_HASH[hash.replace(/^#/, '')];
    const wanted = fromQuery ?? fromHash;

    return wanted && available.includes(wanted) ? wanted : 'invitation';
}

export function tabUrl(tab: GuestTab, href: string = window.location.href): string {
    const url = new URL(href);
    url.hash = '';
    if (tab === 'invitation') url.searchParams.delete(TAB_PARAM);
    else url.searchParams.set(TAB_PARAM, tab);

    return url.pathname + url.search;
}
