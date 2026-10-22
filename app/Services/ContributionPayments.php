<?php

namespace App\Services;

use App\Enums\ContributionStatus;
use App\Exceptions\GiftBudgetExceeded;
use App\Exceptions\PaymentUnavailable;
use App\Mail\ContributionReceiptMail;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\Guest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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

    /** Durée pendant laquelle un paiement en attente réserve sa part d'un cadeau. */
    public const RESERVATION_MINUTES = 60;

    /**
     * Crée la contribution puis la session de paiement MoneyFusion.
     * MoneyFusion exige un numéro de téléphone : celui de l'invité, ou `$payerPhone` s'il n'en a pas (jamais enregistré).
     *
     * @throws GiftBudgetExceeded si le montant dépasse ce qu'il reste à financer pour le cadeau
     * @throws PaymentUnavailable si MoneyFusion refuse ou ne répond pas
     */
    public function start(Guest $guest, int $amount, ?Gift $gift = null, ?string $message = null, ?string $payerPhone = null): Contribution
    {
        $event = $guest->event;

        $contribution = DB::transaction(function () use ($guest, $event, $amount, $gift, $message): Contribution {
            // Verrou sur le cadeau : deux dons concurrents ne peuvent pas dépasser la cible,
            // car chacun voit la réservation (contribution en attente) de l'autre.
            if ($gift) {
                $locked = Gift::whereKey($gift->id)->lockForUpdate()->firstOrFail();
                $reserved = $locked->contributions()
                    ->where(fn ($q) => $q
                        ->where('status', ContributionStatus::Confirmed)
                        ->orWhere(fn ($q) => $q->where('status', ContributionStatus::Pending)->where('created_at', '>=', now()->subMinutes(self::RESERVATION_MINUTES))))
                    ->sum('amount');

                if ($amount > max(0, $locked->target_amount - $reserved)) {
                    throw new GiftBudgetExceeded(__("Ce montant dépasse ce qu'il reste à financer pour ce cadeau."));
                }
            }

            return $guest->contributions()->create([
                'event_id' => $event->id,
                'gift_id' => $gift?->id,
                'amount' => $amount,
                'message' => $message,
                'status' => ContributionStatus::Pending,
            ]);
        });

        try {
            $result = MoneyFusion::createPayment([
                'total_price' => $amount,
                'articles' => [[
                    'name' => $gift?->name ?? 'Cagnotte - '.$event->title,
                    'price' => $amount,
                    'quantity' => 1,
                ]],
                'nom_client' => $guest->name,
                'numero_send' => preg_replace('/\D+/', '', (string) ($payerPhone ?: $guest->phone)),
                'order_id' => $contribution->id,
                'return_url' => route('donations.return', [$guest->token, $contribution]),
                'webhook_url' => route('webhooks.moneyfusion'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Contribution payment could not be created', ['contribution' => $contribution->id, 'error' => $e->getMessage()]);
            $contribution->update(['status' => ContributionStatus::Failed]);

            throw new PaymentUnavailable(__('Le paiement est momentanément indisponible.'), 0, $e);
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

        $justConfirmed = false;

        $status = DB::transaction(function () use ($contribution, $data, $apiStatus, &$justConfirmed): ContributionStatus {
            $locked = Contribution::whereKey($contribution->id)->lockForUpdate()->first();

            if ($locked->status !== ContributionStatus::Pending) {
                return $locked->status;
            }

            if ($apiStatus === 'paid') {
                $amountMatches = $this->paidAmountMatches($data, $locked->amount);

                if (! $amountMatches) {
                    Log::error('Contribution amount mismatch', ['contribution' => $locked->id, 'expected' => $locked->amount, 'received' => $data['Montant']]);
                    $locked->update(['status' => ContributionStatus::Mismatch]);
                } else {
                    $locked->update(['status' => ContributionStatus::Confirmed, 'confirmed_at' => now()]);
                    $justConfirmed = true;
                }
            } elseif ($apiStatus === 'failed') {
                $locked->update(['status' => ContributionStatus::Failed]);
            }

            $contribution->setRawAttributes($locked->getAttributes(), true);

            return $locked->status;
        });

        if ($justConfirmed) {
            $this->sendReceipt($contribution);
        }

        return $status;
    }

    /**
     * Envoie le reçu à l'invité s'il a une adresse e-mail. Un échec d'envoi ne remet jamais en cause la confirmation.
     */
    private function sendReceipt(Contribution $contribution): void
    {
        $contribution->loadMissing(['guest', 'event', 'gift']);

        if (! $contribution->guest->email) {
            return;
        }

        try {
            Mail::to($contribution->guest->email)->send(new ContributionReceiptMail($contribution));
        } catch (\Throwable $e) {
            Log::error('Contribution receipt could not be queued', ['contribution' => $contribution->id, 'error' => $e->getMessage()]);
        }
    }

    /**
     * MoneyFusion renvoie `Montant` net de ses frais (250 F payés => Montant 242,5 + frais 7,5) :
     * le paiement est conforme si le montant brut (Montant + frais) ou le montant seul correspond à la contribution.
     *
     * @param  array<string, mixed>  $data
     */
    private function paidAmountMatches(array $data, int $expected): bool
    {
        if (! isset($data['Montant'])) {
            return true;
        }

        $net = (float) $data['Montant'];
        $gross = $net + (float) ($data['frais'] ?? 0);

        return abs($gross - $expected) < 0.01 || abs($net - $expected) < 0.01;
    }
}
