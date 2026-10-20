import { intlLocale } from '@/lib/locale';

/** Montant en F CFA avec espaces insécables : « 25 000 F CFA ». */
export const fcfa = (amount: number): string => `${new Intl.NumberFormat(intlLocale()).format(amount).replace(/[  ]/g, ' ')} F CFA`;
