/** Distance minimale (px) pour qu'un glissement compte comme un geste volontaire. */
export const SWIPE_THRESHOLD = 50;

export type SwipeDirection = 'left' | 'right' | 'down' | null;

/**
 * Direction d'un glissement tactile. Un geste est horizontal (ou vers le bas) seulement s'il dépasse le seuil
 * et domine nettement l'autre axe : un défilement vertical de la page ne doit pas changer de photo.
 */
export function swipeDirection(startX: number, startY: number, endX: number, endY: number, threshold = SWIPE_THRESHOLD): SwipeDirection {
    const dx = endX - startX;
    const dy = endY - startY;

    if (Math.abs(dx) >= threshold && Math.abs(dx) > Math.abs(dy) * 1.5) {
        return dx < 0 ? 'left' : 'right';
    }

    if (dy >= threshold * 2 && dy > Math.abs(dx) * 1.5) {
        return 'down';
    }

    return null;
}

/** Index voisin dans une liste de `length` éléments, borné (pas de bouclage : les extrémités sont claires). */
export function neighbour(index: number, step: 1 | -1, length: number): number {
    return Math.min(length - 1, Math.max(0, index + step));
}
