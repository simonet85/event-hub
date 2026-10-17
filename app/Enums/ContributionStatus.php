<?php

namespace App\Enums;

enum ContributionStatus: string
{
    /** Paiement créé, en attente de confirmation par MoneyFusion. */
    case Pending = 'pending';

    /** Paiement confirmé par l'API MoneyFusion (seul statut qui compte dans la cagnotte). */
    case Confirmed = 'confirmed';

    case Failed = 'failed';

    /** Jamais payé dans le délai imparti. */
    case Expired = 'expired';

    /** Payé, mais le montant confirmé diffère : à vérifier manuellement par l'hôte. */
    case Mismatch = 'mismatch';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Confirmed => 'Confirmée',
            self::Failed => 'Échouée',
            self::Expired => 'Expirée',
            self::Mismatch => 'À vérifier',
        };
    }
}
