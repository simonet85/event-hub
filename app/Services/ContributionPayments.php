<?php

namespace App\Services;

use App\Enums\ContributionStatus;
use App\Exceptions\PaymentUnavailable;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Simonet85\LaravelMoneyFusion\Facades\MoneyFusion;
use Simonet85\LaravelMoneyFusion\MoneyFusionService;

/**
 * Paiement des contributions via MoneyFusion (package laravel-moneyfusion).
 *
 * MoneyFusion ne signe pas ses webhooks : une contribution n'est JAMAIS créditée sur la foi d'une
 * notification ou d'un retour navigateur. Seule une relecture serveur-à-serveur du statut
 * (paiementNotif/{token}) fait passer une contribution à « confirmée ».
 */
class ContributionPayments
{
    /** Délai au-delà duquel une contribution jamais payée expire. */
    public const EXPIRES_AFTER_HOURS = 48;

    /**
     * Crée la contribution puis la session de paiement MoneyFusion.
     *
     * @throws PaymentUnavailable si MoneyFusion refuse ou ne répond pas
     */
    public function start(Guest $guest, int $amount, ?Gift $gift = null, ?string $message = null): Contribution
    {
        $event = $guest->event;

        $contribution = $guest->contributions()->create([
            'event_id' => $event->id,
            'gift_id' => $gift?->id,
            'amount' => $amount,
            'message' => $message,
            'status' => ContributionStatus::Pending,
        ]);

        try {
            $result = MoneyFusion::createPayment([
                'total_price' => $amount,
                'articles' => [[
                    'name' => $gift?->name ?? 'Cagnotte - '.$event->title,
                    'price' => $amount,
                    'quantity' => 1,
                ]],
                'nom_client' => $guest->name,
                'numero_send' => preg_replace('/\D+/', '', (string) $guest->phone),
                'order_id' => $contribution->id,
                'return_url' => route('donations.return', [$guest->token, $contribution]),
                'webhook_url' => route('webhooks.moneyfusion'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Contribution payment could not be created', ['contribution' => $contribution->id, 'error' => $e->getMessage()]);
            $contribution->update(['status' => ContributionStatus::Failed]);

            throw new PaymentUnavailable('Le paiement est momentanément indisponible.', 0, $e);
        }

        $contribution->update(['payment_token' => $result['token'], 'payment_url' => $result['url']]);

        return $contribution;
    }

    /**
     * Relit le statut auprès de MoneyFusion et met la contribution à jour (idempotent).
     *
     * @throws PaymentUnavailable si le statut n'a pas pu être vérifié auprès de l'API
     */
    public function verify(Contribution $contribution): ContributionStatus
    {
        if ($contribution->status !== ContributionStatus::Pending || ! $contribution->payment_token) {
            return $contribution->status;
        }

        try {
            $result = MoneyFusion::checkPaymentStatus($contribution->payment_token);
        } catch (\Throwable $e) {
            throw new PaymentUnavailable('Vérification MoneyFusion impossible.', 0, $e);
        }

        // Réponse de repli (base locale) : jamais une preuve de paiement.
        if (! ($result['verified'] ?? false)) {
            throw new PaymentUnavailable('Statut MoneyFusion non vérifiable pour le moment.');
        }

        $data = $result['data'] ?? [];
        $apiStatus = MoneyFusionService::normalizeStatus($data['statut'] ?? null);

        return DB::transaction(function () use ($contribution, $data, $apiStatus): ContributionStatus {
            $locked = Contribution::whereKey($contribution->id)->lockForUpdate()->first();

            if ($locked->status !== ContributionStatus::Pending) {
                return $locked->status;
            }

            if ($apiStatus === 'paid') {
                $amountMatches = ! isset($data['Montant']) || (int) $data['Montant'] === $locked->amount;

                if (! $amountMatches) {
                    Log::error('Contribution amount mismatch', ['contribution' => $locked->id, 'expected' => $locked->amount, 'received' => $data['Montant']]);
                    $locked->update(['status' => ContributionStatus::Mismatch]);
                } else {
                    $locked->update(['status' => ContributionStatus::Confirmed, 'confirmed_at' => now()]);
                }
            } elseif ($apiStatus === 'failed') {
                $locked->update(['status' => ContributionStatus::Failed]);
            }

            $contribution->setRawAttributes($locked->getAttributes(), true);

            return $locked->status;
        });
    }
}
