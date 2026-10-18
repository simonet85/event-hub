/** Montant en F CFA avec séparateur d'espace insécable : « 25 000 F CFA ». */
export const fcfa = (amount: number): string => `${new Intl.NumberFormat('fr-FR').format(amount).replace(/[  ]/g, ' ')} F CFA`;
